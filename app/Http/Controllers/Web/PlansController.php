<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\OtpService;
use App\Services\PlanService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * maqsusi.care/plans: prices, phone OTP sign-in and Razorpay checkout.
 * Only people who already have an account in one of the apps can buy, so a
 * plan always lands on a real patient or pharmacy.
 */
class PlansController extends Controller
{
    public function index(Request $request, PlanService $plans)
    {
        $user = $this->signedIn($request);

        return view('site.plans', [
            'plans' => config('plans.plans'),
            'trialDays' => (int) config('plans.trial_days'),
            'user' => $user,
            'status' => $user ? $plans->status($user) : null,
            'payable' => app(RazorpayService::class)->configured(),
            'razorpayKey' => config('services.razorpay.key_id'),
        ]);
    }

    public function sendOtp(Request $request, OtpService $otp)
    {
        $data = $request->validate(['mobile_number' => 'required|digits_between:10,15']);

        $user = User::where('mobile_number', $data['mobile_number'])->whereIn('role', ['user', 'vendor'])->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No Mcare account uses this number. Please sign up in the Mcare+ or Mcare+ Partner app first.',
            ], 404);
        }

        if ($error = $otp->send($data['mobile_number'])) {
            return response()->json(['success' => false, 'message' => $error[0]], $error[2]);
        }

        return response()->json(['success' => true]);
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'mobile_number' => 'required|digits_between:10,15',
            'otp' => 'required|digits:4',
        ]);

        $result = $otp->check($data['mobile_number'], $data['otp']);
        if ($result !== 'ok') {
            $messages = [
                'invalid' => 'That code is not right. Please try again.',
                'expired' => 'That code has expired. Please ask for a new one.',
                'locked' => 'Too many wrong attempts. Please ask for a new code.',
            ];

            return response()->json(['success' => false, 'message' => $messages[$result]], $result === 'locked' ? 429 : 401);
        }

        $user = User::where('mobile_number', $data['mobile_number'])->whereIn('role', ['user', 'vendor'])->firstOrFail();
        $request->session()->regenerate();
        $request->session()->put('plan_user_id', $user->id);

        return response()->json(['success' => true]);
    }

    public function signOut(Request $request)
    {
        $request->session()->forget('plan_user_id');
        $request->session()->regenerate();

        return redirect('/plans');
    }

    public function createOrder(Request $request, RazorpayService $razorpay)
    {
        $user = $this->signedIn($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please sign in first.'], 401);
        }

        $data = $request->validate([
            'plan' => 'required|string',
            'period' => 'required|in:monthly,yearly',
        ]);

        $plan = config('plans.plans.'.$data['plan']);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Unknown plan.'], 422);
        }
        if ($plan['role'] !== $user->role) {
            $message = $user->role === 'vendor'
                ? 'This number is a pharmacy account. Please choose Pharmacy Premium.'
                : 'This number is a patient account. Please choose Mcare Family.';

            return response()->json(['success' => false, 'message' => $message], 422);
        }
        if (!$razorpay->configured()) {
            return response()->json(['success' => false, 'message' => 'Online payment is not switched on yet.'], 503);
        }

        $amount = (int) $plan['prices'][$data['period']]['amount'];
        $receipt = 'plan_'.$user->id.'_'.Str::lower(Str::random(10));

        try {
            $orderId = $razorpay->createOrder($amount, $receipt, [
                'user_id' => (string) $user->id,
                'plan' => $data['plan'],
                'period' => $data['period'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => 'Could not start the payment. Please try again.'], 502);
        }

        Payment::create([
            'user_id' => $user->id,
            'plan' => $data['plan'],
            'period' => $data['period'],
            'amount' => $amount,
            'razorpay_order_id' => $orderId,
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $orderId,
            'amount' => $amount,
            'name' => config('plans.business.name'),
            'description' => $plan['name'].' ('.$data['period'].')',
            'prefill' => ['name' => $user->name, 'contact' => $user->mobile_number],
        ]);
    }

    /** Razorpay Checkout calls this in the browser after a successful payment. */
    public function confirm(Request $request, RazorpayService $razorpay, PlanService $plans)
    {
        $user = $this->signedIn($request);
        $data = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (!$user || !$payment || (int) $payment->user_id !== (int) $user->id
            || !$razorpay->validPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            return response()->json(['success' => false, 'message' => 'We could not confirm this payment. If money was taken, it will be added within a few minutes, or contact us.'], 422);
        }

        $plans->markPaid($payment, $data['razorpay_payment_id']);

        return response()->json(['success' => true, 'status' => $plans->status($user)]);
    }

    /**
     * Server-to-server backup for when the browser closes before confirm().
     * Set this URL and its secret in Razorpay > Settings > Webhooks.
     */
    public function webhook(Request $request, RazorpayService $razorpay, PlanService $plans)
    {
        $body = $request->getContent();
        if (!$razorpay->validWebhookSignature($body, (string) $request->header('X-Razorpay-Signature'))) {
            return response()->json(['ok' => false], 400);
        }

        $event = json_decode($body, true) ?: [];
        if (in_array($event['event'] ?? '', ['payment.captured', 'order.paid'], true)) {
            $entity = data_get($event, 'payload.payment.entity', []);
            $payment = Payment::where('razorpay_order_id', $entity['order_id'] ?? '')->first();

            if ($payment && (int) ($entity['amount'] ?? 0) === (int) $payment->amount) {
                $plans->markPaid($payment, $entity['id'] ?? null);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function signedIn(Request $request): ?User
    {
        $id = $request->session()->get('plan_user_id');

        return $id ? User::find($id) : null;
    }
}

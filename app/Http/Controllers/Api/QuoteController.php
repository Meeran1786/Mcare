<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Pharmacy;
use App\Models\Prescription;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

/**
 * Price before preparing. The pharmacy sends the price of an accepted order,
 * the patient approves or declines it, and only then can the pharmacy start
 * preparing. Orders without a quote (from older Partner apps) work as before.
 */
class QuoteController extends Controller
{
    private const QUOTABLE = ['pending', 'accepted'];

    public function send(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
            'note' => 'nullable|string|max:500',
        ]);

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();
        $order = $pharmacy ? Order::where('id', $id)->where('pharmacy_id', $pharmacy->id)->first() : null;

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        if (!in_array($order->status, self::QUOTABLE, true) || $order->quote_status === 'approved') {
            return response()->json(['success' => false, 'message' => 'The price can only be sent before preparing.'], 422);
        }

        // Older patient apps have no approve button, so the order would be
        // stuck. Only the 2.0 app sets local_reminders; for anyone else the
        // price is saved as approved and the pharmacy carries on as before.
        $patientCanApprove = (bool) $order->user?->local_reminders;

        $order->update([
            'quote_amount' => $data['amount'],
            'quote_note' => $data['note'] ?? null,
            'quote_status' => $patientCanApprove ? 'pending' : 'approved',
            'quoted_at' => now(),
            'quote_responded_at' => $patientCanApprove ? null : now(),
            'total_amount' => $data['amount'],
            'status' => 'accepted',
        ]);

        $amount = $this->rupees($order->quote_amount);
        $this->notify(
            $order->user_id,
            'Price from ' . $pharmacy->pharmacy_name,
            $patientCanApprove
                ? "Your medicines cost {$amount}. Open Mcare+ to approve the order."
                : "Your medicines cost {$amount}.",
            'QUOTE_SENT',
            $order->id
        );

        return response()->json([
            'success' => true,
            'data' => $order->fresh(),
            'auto_approved' => !$patientCanApprove,
        ]);
    }

    public function approve(Request $request, $id)
    {
        return $this->respond($request, $id, 'approved');
    }

    public function decline(Request $request, $id)
    {
        return $this->respond($request, $id, 'declined');
    }

    private function respond(Request $request, $id, string $answer)
    {
        $order = Order::with('pharmacy')->where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        if ($order->quote_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'There is no price waiting for your answer.'], 422);
        }

        $update = ['quote_status' => $answer, 'quote_responded_at' => now()];
        if ($answer === 'declined') {
            $update['status'] = 'cancelled';
            if ($order->prescription_id) {
                Prescription::where('id', $order->prescription_id)->update(['status' => 'cancelled']);
            }
        }
        $order->update($update);

        $vendorId = $order->pharmacy?->user_id;
        if ($vendorId) {
            $amount = $this->rupees($order->quote_amount);
            $answer === 'approved'
                ? $this->notify($vendorId, "Order #{$order->id} approved", "The patient approved {$amount}. You can prepare the medicines now.", 'QUOTE_APPROVED', $order->id)
                : $this->notify($vendorId, "Order #{$order->id} declined", "The patient declined the price of {$amount}. The order is cancelled.", 'QUOTE_DECLINED', $order->id);
        }

        return response()->json(['success' => true, 'data' => $order->fresh()]);
    }

    private function rupees($amount): string
    {
        $value = (float) $amount;
        return '₹' . (fmod($value, 1.0) == 0.0 ? number_format($value, 0) : number_format($value, 2));
    }

    private function notify(int $userId, string $title, string $body, string $type, int $orderId): void
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => 'order',
            'reference_id' => $orderId,
            'is_read' => false,
        ]);

        $token = \App\Models\User::where('id', $userId)->value('fcm_token');
        if ($token) {
            try {
                app(FirebaseService::class)->sendNotification($token, $title, $body, [
                    'type' => $type,
                    'order_id' => (string) $orderId,
                    'channel_id' => $type === 'QUOTE_SENT' ? 'app_channel_v2' : 'admin_channel_v2',
                    'sound' => 'app_push_sound',
                ]);
            } catch (\Throwable $e) {
                \Log::error('Quote push failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        }
    }
}

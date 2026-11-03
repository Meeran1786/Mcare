<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Which plan an account is on, and what that plan allows. Patients and
 * pharmacies are free by default; paid time comes from website payments,
 * a trial for new accounts, or an admin grant.
 */
class PlanService
{
    /** The paid plan that fits this account's role, or null for admins. */
    public function planFor(User $user): ?string
    {
        foreach (config('plans.plans') as $key => $plan) {
            if ($plan['role'] === $user->role) {
                return $key;
            }
        }

        return null;
    }

    /** Starts the free trial the first time a new account is seen. */
    public function startTrialIfNew(User $user): void
    {
        $days = (int) config('plans.trial_days');
        $plan = $this->planFor($user);

        if ($days <= 0 || !$plan || Subscription::where('user_id', $user->id)->exists()) {
            return;
        }

        Subscription::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'kind' => 'trial',
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
        ]);
    }

    public function current(User $user): ?Subscription
    {
        return Subscription::current()->where('user_id', $user->id)->orderByDesc('ends_at')->first();
    }

    /** The latest end date across all paid or trial time, for "valid until". */
    public function endsAt(User $user): ?Carbon
    {
        $end = Subscription::where('user_id', $user->id)->where('ends_at', '>', now())->max('ends_at');

        return $end ? Carbon::parse($end) : null;
    }

    public function limit(User $user, string $key): ?int
    {
        $sub = $this->current($user);
        $limits = config('plans.limits.'.($sub ? $sub->plan : 'free'), []);

        return $limits[$key] ?? config('plans.limits.free.'.$key);
    }

    /** What the apps show. No prices, by store rules. */
    public function status(User $user): array
    {
        $sub = $this->current($user);
        $endsAt = $sub ? $this->endsAt($user) : null;
        $plan = $sub ? config('plans.plans.'.$sub->plan) : null;

        return [
            'plan' => $sub?->plan ?? 'free',
            'plan_name' => $plan['name'] ?? 'Free',
            'is_trial' => $sub?->kind === 'trial',
            'ends_at' => $endsAt?->toIso8601String(),
            'days_left' => $endsAt ? (int) ceil(now()->diffInHours($endsAt) / 24) : null,
            'family_members' => $this->limit($user, 'family_members'),
        ];
    }

    /**
     * Marks a payment paid and adds its time to the account. Safe to call more
     * than once for the same payment (browser callback and webhook both do).
     */
    public function markPaid(Payment $payment, ?string $razorpayPaymentId): bool
    {
        return DB::transaction(function () use ($payment, $razorpayPaymentId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === 'paid') {
                return false;
            }

            $payment->update([
                'status' => 'paid',
                'razorpay_payment_id' => $razorpayPaymentId,
                'paid_at' => now(),
            ]);

            $days = (int) config("plans.plans.{$payment->plan}.prices.{$payment->period}.days", 30);
            $this->addTime($payment->user, $payment->plan, $payment->period, $days, $payment->id);

            return true;
        });
    }

    /** Paid time starts when earlier paid time ends, so nobody loses days. */
    public function addTime(User $user, string $plan, string $kind, int $days, ?int $paymentId = null): Subscription
    {
        $paidEnd = Subscription::where('user_id', $user->id)->where('kind', '!=', 'trial')
            ->where('ends_at', '>', now())->max('ends_at');
        $start = $paidEnd ? Carbon::parse($paidEnd) : now();

        return Subscription::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'kind' => $kind,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays($days),
            'payment_id' => $paymentId,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CaregiverLink;
use App\Models\Notification;
use App\Models\Reminder;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Tells patients (and their linked family) a few days before a medicine runs
 * out, once per tablet count. Only reminders with a tablet count are checked.
 */
class CheckRefills extends Command
{
    protected $signature = 'reminders:check-refills {--days=3 : Alert when this many days of tablets or fewer are left}';
    protected $description = 'Alert patients before their tablets run out';

    public function handle(FirebaseService $firebase)
    {
        $threshold = max(1, (int) $this->option('days'));
        $count = 0;

        $reminders = Reminder::where('is_active', true)
            ->whereNotNull('pills_left')
            ->whereNull('refill_alerted_at')
            ->with('user')
            ->get();

        foreach ($reminders as $reminder) {
            $daysLeft = $reminder->daysLeft();
            if ($daysLeft === null || $daysLeft > $threshold || !$reminder->user) {
                continue;
            }

            $medicine = $reminder->medicine_name ?: 'your medicine';
            $left = (int) floor($reminder->pillsRemaining());
            $title = 'Time to refill';
            $body = $daysLeft <= 0
                ? "{$medicine} has run out. Tap to order more."
                : "Only {$left} tablets of {$medicine} left, enough for about {$daysLeft} days. Tap to order more.";

            // The new app rings its own refill warning, like its dose alarms.
            $this->notify($firebase, $reminder->user, $title, $body, $reminder->id, !$reminder->user->local_reminders);

            $name = $reminder->user->name ?: $reminder->user->mobile_number;
            $links = CaregiverLink::active()->where('patient_id', $reminder->user_id)->with('caregiver')->get();
            foreach ($links as $link) {
                $this->notify(
                    $firebase,
                    $link->caregiver,
                    'Refill needed',
                    "{$name} has about {$daysLeft} days of {$medicine} left.",
                    $reminder->id
                );
            }

            $reminder->forceFill(['refill_alerted_at' => now()])->save();
            $count++;
        }

        $this->info("Refill alerts sent: {$count}");
        return self::SUCCESS;
    }

    private function notify(FirebaseService $firebase, $user, string $title, string $body, int $reminderId, bool $push = true): void
    {
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => 'refill',
            'reference_id' => $reminderId,
            'is_read' => false,
        ]);

        if (!$push || !$user->fcm_token) {
            return;
        }

        try {
            $firebase->sendNotification($user->fcm_token, $title, $body, [
                'type' => 'REFILL_DUE',
                'reminder_id' => (string) $reminderId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Refill push failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}

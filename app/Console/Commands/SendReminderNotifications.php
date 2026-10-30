<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\FirebaseService;
use App\Services\ReminderNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReminderNotifications extends Command
{
    protected $signature = 'reminder:send {--grace=10 : Number of minutes after reminder time that can still be sent}';
    protected $description = 'Send reminder notifications';

    public function handle()
    {
        $graceMinutes = max(1, (int) $this->option('grace'));

        $reminders = Reminder::where('is_active', true)
            ->with('user')
            ->get()
            ->filter(function ($reminder) {
                $timezone = $reminder->timezone ?: config('app.timezone');
                $graceMinutes = max(1, (int) $this->option('grace'));
                $now = Carbon::now($timezone);
                $reminderTime = Carbon::now($timezone)
                    ->setTimeFromTimeString($reminder->time);
                $windowEnd = $reminderTime->copy()->addMinutes($graceMinutes);
                $sendKey = $now->format('Y-m-d') . ' ' . $reminderTime->format('H:i');
                $isReminderDay = in_array($now->format('D'), $reminder->days ?? [], true);
                $isInsideDueWindow = $now->greaterThanOrEqualTo($reminderTime)
                    && $now->lessThan($windowEnd);
                $wasAlreadySent = $reminder->last_sent_key === $sendKey;

                Log::info('Reminder checking', [
                    'reminder_id' => $reminder->id,
                    'now' => $now->format('Y-m-d H:i:s'),
                    'reminder_time' => $reminderTime->format('Y-m-d H:i:s'),
                    'window_end' => $windowEnd->format('Y-m-d H:i:s'),
                    'day_now' => $now->format('D'),
                    'days' => $reminder->days,
                    'last_sent_key' => $reminder->last_sent_key,
                    'send_key' => $sendKey,
                    'is_reminder_day' => $isReminderDay,
                    'is_inside_due_window' => $isInsideDueWindow,
                    'was_already_sent' => $wasAlreadySent,
                ]);

                return $isReminderDay && $isInsideDueWindow && !$wasAlreadySent;
            });

        Log::info('Reminder notification scheduler checked', [
            'server_time' => now()->toDateTimeString(),
            'matched_count' => $reminders->count(),
            'grace_minutes' => $graceMinutes,
        ]);

        $firebase = app(FirebaseService::class);
        $service = app(ReminderNotificationService::class);

        foreach ($reminders as $reminder) {

            if (!$reminder->user || !$reminder->user->fcm_token) {

                Log::warning('Reminder notification skipped: missing user or FCM token', [
                    'reminder_id' => $reminder->id,
                    'user_id' => $reminder->user_id,
                ]);

                continue;
            }

            try {

                $service->send($reminder, $firebase);

                $timezone = $reminder->timezone ?: config('app.timezone');
                $now = Carbon::now($timezone);
                $reminder->forceFill([
                    'last_sent_at' => $now,
                    'last_sent_key' => $now->format('Y-m-d') . ' ' . Carbon::parse($reminder->time, $timezone)->format('H:i'),
                ])->save();

                Log::info('Reminder sent successfully', [
                    'reminder_id' => $reminder->id,
                    'user_id' => $reminder->user_id,
                ]);

                $this->info("Reminder sent for ID: {$reminder->id}");

            } catch (\Exception $e) {

                Log::error('Reminder notification failed', [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminder scheduler matched {$reminders->count()} reminder(s).");
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CaregiverLink;
use App\Models\DoseLog;
use App\Models\Notification;
use App\Models\Reminder;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Marks today's doses as missed when the patient has not answered the
 * reminder within the grace period, and alerts their family members.
 * Only patients with at least one linked family member are checked.
 */
class CheckMissedDoses extends Command
{
    protected $signature = 'doses:check-missed {--grace=30 : Minutes after the dose time before it counts as missed}';
    protected $description = 'Record missed doses and alert family members';

    public function handle(FirebaseService $firebase)
    {
        $grace = max(5, (int) $this->option('grace'));

        $links = CaregiverLink::active()->with('caregiver')->get()->groupBy('patient_id');
        if ($links->isEmpty()) {
            $this->info('No family links.');
            return self::SUCCESS;
        }

        $reminders = Reminder::where('is_active', true)
            ->whereIn('user_id', $links->keys())
            ->with('user')
            ->get();

        $count = 0;
        foreach ($reminders as $reminder) {
            $tz = $reminder->timezone ?: config('app.timezone');
            $now = Carbon::now($tz);
            if (!in_array($now->format('D'), $reminder->days ?? [], true)) {
                continue;
            }

            $at = $now->copy()->setTimeFromTimeString($reminder->time);
            // Only today's doses, from the end of the grace period until midnight.
            if ($now->lessThan($at->copy()->addMinutes($grace))) {
                continue;
            }

            $key = $at->format('Y-m-d H:i');
            $log = DoseLog::firstOrCreate(
                ['reminder_id' => $reminder->id, 'scheduled_for' => $key],
                ['user_id' => $reminder->user_id, 'status' => 'missed']
            );

            if ($log->status !== 'missed' || $log->caregivers_notified_at) {
                continue;
            }

            $patientName = $reminder->user->name ?: $reminder->user->mobile_number;
            $medicine = $reminder->medicine_name ?: 'medicine';
            $title = 'Missed dose';
            $body = "{$patientName} has not taken {$medicine} due at {$at->format('h:i A')}.";

            foreach ($links[$reminder->user_id] as $link) {
                $caregiver = $link->caregiver;

                Notification::create([
                    'user_id' => $caregiver->id,
                    'title' => $title,
                    'body' => $body,
                    'type' => 'missed_dose',
                    'reference_id' => $reminder->user_id,
                    'is_read' => false,
                ]);

                if ($caregiver->fcm_token) {
                    try {
                        $firebase->sendNotification($caregiver->fcm_token, $title, $body, [
                            'type' => 'MISSED_DOSE',
                            'patient_id' => (string) $reminder->user_id,
                            'reminder_id' => (string) $reminder->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Missed dose push failed', ['caregiver_id' => $caregiver->id, 'error' => $e->getMessage()]);
                    }
                }
            }

            $log->forceFill(['caregivers_notified_at' => now()])->save();
            $count++;
        }

        $this->info("Missed doses alerted: {$count}");
        return self::SUCCESS;
    }
}

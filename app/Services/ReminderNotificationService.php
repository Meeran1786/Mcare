<?php

namespace App\Services;

use App\Models\Notification;

class ReminderNotificationService
{
    public function send($reminder, $firebase)
    {
        $user = $reminder->user;

        if (!$user || !$user->fcm_token) return;

        $title = $reminder->medicine_name ?? 'Medicine Reminder';
        $message = $reminder->note ?? 'Time to take your medicine';

        // 🔔 Send Push, unless the app already rings this reminder as a local alarm
        if (!$user->local_reminders) {
            $firebase->sendNotification(
                $user->fcm_token,
                $title,
                $message,
                [
                    'type' => 'MEDICINE_REMINDER',
                    'reminder_id' => (string) $reminder->id,
                    'sound' => 'reminder_sound',
                    'channel_id' => 'reminder_channel',
                    'click_action' => 'OPEN_MCARE_APP',
                ]
            );
        }

        // 💾 Save in DB
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $message,
            'type' => 'medicine_reminder',
            'reference_id' => $reminder->id,
            'is_read' => false
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFirebase(): object
    {
        return new class {
            public array $sent = [];

            public function sendNotification($token, $title, $message, $extra = [])
            {
                $this->sent[] = $token;
            }
        };
    }

    private function reminderFor(User $user): Reminder
    {
        return Reminder::create([
            'user_id' => $user->id,
            'time' => '08:00',
            'days' => ['Mon'],
            'is_active' => true,
            'medicine_name' => 'Metformin',
        ]);
    }

    public function test_save_fcm_token_stores_local_reminders_flag(): void
    {
        $user = User::create(['mobile_number' => '9000000101', 'role' => 'user']);

        $this->actingAs($user)
            ->postJson('/api/save-fcm-token', ['fcm_token' => 'tok-new-app', 'local_reminders' => true])
            ->assertOk();
        $this->assertTrue($user->fresh()->local_reminders);

        // Signing in on an old app (no flag) turns the server push back on.
        $this->actingAs($user)
            ->postJson('/api/save-fcm-token', ['fcm_token' => 'tok-old-app'])
            ->assertOk();
        $this->assertFalse($user->fresh()->local_reminders);
    }

    public function test_reminder_push_is_skipped_for_local_reminder_users_but_saved_in_app(): void
    {
        $user = User::create(['mobile_number' => '9000000102', 'role' => 'user', 'fcm_token' => 'tok', 'local_reminders' => true]);
        $firebase = $this->fakeFirebase();

        app(ReminderNotificationService::class)->send($this->reminderFor($user)->load('user'), $firebase);

        $this->assertSame([], $firebase->sent);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'medicine_reminder']);
    }

    public function test_reminder_push_is_still_sent_to_old_app_users(): void
    {
        $user = User::create(['mobile_number' => '9000000103', 'role' => 'user', 'fcm_token' => 'tok-old']);
        $firebase = $this->fakeFirebase();

        app(ReminderNotificationService::class)->send($this->reminderFor($user)->load('user'), $firebase);

        $this->assertSame(['tok-old'], $firebase->sent);
    }
}

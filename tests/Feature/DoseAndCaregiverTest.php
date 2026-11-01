<?php

namespace Tests\Feature;

use App\Models\CaregiverLink;
use App\Models\DoseLog;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoseAndCaregiverTest extends TestCase
{
    use RefreshDatabase;

    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $test = $this;
        $this->app->instance(FirebaseService::class, new class($test) extends FirebaseService {
            public function __construct(private $test) {}
            public function sendNotification($fcmToken, $title, $message, $extraData = [])
            {
                $this->test->recordPush($fcmToken, $message);
                return true;
            }
        });
        // A Monday, 09:00 in the reminders' time zone.
        Carbon::setTestNow(Carbon::parse('2026-11-02 09:00', 'Asia/Kolkata'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function recordPush($token, $message): void
    {
        $this->pushes[] = [$token, $message];
    }

    private function user(string $mobile, string $name, ?string $token = null): User
    {
        return User::create(['mobile_number' => $mobile, 'name' => $name, 'role' => 'user', 'fcm_token' => $token]);
    }

    private function reminder(User $user, string $time): Reminder
    {
        return Reminder::create([
            'user_id' => $user->id, 'time' => $time, 'days' => ['Mon'], 'is_active' => true,
            'medicine_name' => 'Metformin', 'timezone' => 'Asia/Kolkata',
        ]);
    }

    private function link(User $patient, User $caregiver): void
    {
        $code = $this->actingAs($patient)->postJson('/api/caregivers/invite')->assertOk()->json('data.code');
        $this->actingAs($caregiver)->postJson('/api/caregivers/accept', ['code' => $code])
            ->assertOk()->assertJsonPath('data.patient_id', $patient->id);
    }

    public function test_patient_marks_dose_taken_and_it_is_idempotent(): void
    {
        $patient = $this->user('9000000201', 'Amma');
        $r = $this->reminder($patient, '08:00');

        $this->actingAs($patient)->postJson('/api/doses', ['reminder_id' => $r->id, 'scheduled_for' => '2026-11-02 08:00', 'status' => 'skipped'])->assertOk();
        $this->actingAs($patient)->postJson('/api/doses', ['reminder_id' => $r->id, 'scheduled_for' => '2026-11-02 08:00', 'status' => 'taken'])
            ->assertOk()->assertJsonPath('data.status', 'taken');

        $this->assertSame(1, DoseLog::count());
        $this->actingAs($patient)->getJson('/api/doses?from=2026-11-02&to=2026-11-02')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cannot_log_dose_for_someone_elses_reminder(): void
    {
        $patient = $this->user('9000000202', 'Amma');
        $other = $this->user('9000000203', 'Stranger');
        $r = $this->reminder($patient, '08:00');

        $this->actingAs($other)->postJson('/api/doses', ['reminder_id' => $r->id, 'scheduled_for' => '2026-11-02 08:00', 'status' => 'taken'])->assertNotFound();
    }

    public function test_invite_code_links_family_and_expires(): void
    {
        $patient = $this->user('9000000204', 'Amma');
        $son = $this->user('9000000205', 'Ravi');
        $this->link($patient, $son);

        $this->actingAs($patient)->getJson('/api/caregivers')->assertJsonPath('data.my_caregivers.0.name', 'Ravi');
        $this->actingAs($son)->getJson('/api/caregivers')->assertJsonPath('data.i_care_for.0.name', 'Amma');

        // Expired code
        $code = $this->actingAs($patient)->postJson('/api/caregivers/invite')->json('data.code');
        Carbon::setTestNow(now()->addMinutes(31));
        $daughter = $this->user('9000000206', 'Sita');
        $this->actingAs($daughter)->postJson('/api/caregivers/accept', ['code' => $code])->assertStatus(422);

        // Own code
        $code = $this->actingAs($patient)->postJson('/api/caregivers/invite')->json('data.code');
        $this->actingAs($patient)->postJson('/api/caregivers/accept', ['code' => $code])->assertStatus(422);
    }

    public function test_summary_only_for_linked_caregiver(): void
    {
        $patient = $this->user('9000000207', 'Amma');
        $son = $this->user('9000000208', 'Ravi');
        $stranger = $this->user('9000000209', 'X');
        $r = $this->reminder($patient, '08:00');
        $this->reminder($patient, '21:00');
        DoseLog::create(['user_id' => $patient->id, 'reminder_id' => $r->id, 'scheduled_for' => '2026-11-02 08:00', 'status' => 'taken']);

        $this->actingAs($stranger)->getJson("/api/caregivers/patients/{$patient->id}/summary")->assertNotFound();

        $this->link($patient, $son);
        $this->actingAs($son)->getJson("/api/caregivers/patients/{$patient->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.today.0.status', 'taken')
            ->assertJsonPath('data.today.1.status', 'upcoming')
            ->assertJsonPath('data.last_7_days.taken', 1);
    }

    public function test_missed_dose_alerts_family_once(): void
    {
        $patient = $this->user('9000000210', 'Amma');
        $son = $this->user('9000000211', 'Ravi', 'son-token');
        $missed = $this->reminder($patient, '08:00');   // 60 min ago: missed
        $this->reminder($patient, '08:45');             // 15 min ago: still in grace
        $taken = $this->reminder($patient, '07:00');
        DoseLog::create(['user_id' => $patient->id, 'reminder_id' => $taken->id, 'scheduled_for' => '2026-11-02 07:00', 'status' => 'taken']);
        $this->link($patient, $son);

        $this->artisan('doses:check-missed')->assertSuccessful();
        $this->artisan('doses:check-missed')->assertSuccessful();

        $this->assertSame('missed', DoseLog::where('reminder_id', $missed->id)->value('status'));
        $this->assertCount(1, $this->pushes);
        $this->assertSame('son-token', $this->pushes[0][0]);
        $this->assertStringContainsString('Amma', $this->pushes[0][1]);
        $this->assertSame(1, Notification::where('user_id', $son->id)->where('type', 'missed_dose')->count());
    }

    public function test_no_alerts_without_family(): void
    {
        $patient = $this->user('9000000212', 'Amma');
        $this->reminder($patient, '08:00');

        $this->artisan('doses:check-missed')->assertSuccessful();
        $this->assertSame(0, DoseLog::count());
    }
}

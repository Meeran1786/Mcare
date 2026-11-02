<?php

namespace Tests\Feature;

use App\Models\CaregiverLink;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Pharmacy;
use App\Models\Reminder;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteAndRefillTest extends TestCase
{
    use RefreshDatabase;

    public array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $test = $this;
        $this->app->instance(FirebaseService::class, new class($test) extends FirebaseService {
            public function __construct(private $test) {}
            public function sendNotification($fcmToken, $title, $message, $extraData = [])
            {
                $this->test->pushes[] = [$fcmToken, $message, $extraData['type'] ?? null];
                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function order(): array
    {
        $patient = User::create(['mobile_number' => '9000000401', 'name' => 'Amma', 'role' => 'user', 'fcm_token' => 'patient-tok', 'local_reminders' => true]);
        $vendor = User::create(['mobile_number' => '9000000402', 'name' => 'Owner', 'role' => 'vendor', 'fcm_token' => 'vendor-tok']);
        $pharmacy = Pharmacy::forceCreate([
            'user_id' => $vendor->id,
            'pharmacy_name' => 'Care Pharmacy',
            'owner_name' => 'Owner',
            'license_number' => 'L1',
            'registration_number' => 'R1',
            'address_line' => 'Road 1',
            'area' => 'Ameerpet',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500001',
            'latitude' => 17.4,
            'longitude' => 78.4,
        ]);
        $order = Order::create([
            'user_id' => $patient->id,
            'pharmacy_id' => $pharmacy->id,
            'status' => 'pending',
            'delivery_lat' => 17.4,
            'delivery_lng' => 78.4,
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
        ]);
        return [$patient, $vendor, $order];
    }

    public function test_patient_approves_price_before_pharmacy_prepares(): void
    {
        [$patient, $vendor, $order] = $this->order();

        $this->actingAs($vendor)->postJson("/api/vendor/orders/{$order->id}/quote", ['amount' => 450, 'note' => 'Strip of 10'])
            ->assertOk()->assertJsonPath('data.quote_status', 'pending');
        $this->assertSame(['patient-tok', 'Your medicines cost ₹450. Open Mcare+ to approve the order.', 'QUOTE_SENT'], $this->pushes[0]);

        // Cannot prepare yet
        $this->actingAs($vendor)->putJson("/api/vendor/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertStatus(422)->assertJsonPath('waiting_for_quote', true);

        // Someone else's order
        $other = User::create(['mobile_number' => '9000000403', 'role' => 'user']);
        $this->actingAs($other)->postJson("/api/user/orders/{$order->id}/quote/approve")->assertNotFound();

        $this->actingAs($patient)->postJson("/api/user/orders/{$order->id}/quote/approve")
            ->assertOk()->assertJsonPath('data.quote_status', 'approved');
        $this->assertSame('vendor-tok', $this->pushes[1][0]);

        $this->actingAs($vendor)->putJson("/api/vendor/orders/{$order->id}/status", ['status' => 'preparing'])->assertOk();
        $this->assertSame('450.00', (string) $order->fresh()->total_amount);

        // No new price after approval
        $this->actingAs($vendor)->postJson("/api/vendor/orders/{$order->id}/quote", ['amount' => 900])->assertStatus(422);
    }

    public function test_declining_the_price_cancels_the_order(): void
    {
        [$patient, $vendor, $order] = $this->order();
        $this->actingAs($vendor)->postJson("/api/vendor/orders/{$order->id}/quote", ['amount' => 450])->assertOk();

        $this->actingAs($patient)->postJson("/api/user/orders/{$order->id}/quote/decline")->assertOk();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->actingAs($patient)->postJson("/api/user/orders/{$order->id}/quote/approve")->assertStatus(422);
    }

    public function test_price_for_a_patient_on_the_old_app_is_approved_at_once(): void
    {
        [$patient, $vendor, $order] = $this->order();
        $patient->update(['local_reminders' => false]);

        $this->actingAs($vendor)->postJson("/api/vendor/orders/{$order->id}/quote", ['amount' => 300])
            ->assertOk()->assertJsonPath('auto_approved', true)->assertJsonPath('data.quote_status', 'approved');
        $this->actingAs($vendor)->putJson("/api/vendor/orders/{$order->id}/status", ['status' => 'preparing'])->assertOk();
    }

    public function test_orders_without_a_price_work_as_before(): void
    {
        [, $vendor, $order] = $this->order();
        $this->actingAs($vendor)->putJson("/api/vendor/orders/{$order->id}/status", ['status' => 'preparing'])->assertOk();
    }

    public function test_tablets_left_counts_down_with_the_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:00', 'Asia/Kolkata')); // Monday
        $user = User::create(['mobile_number' => '9000000404', 'role' => 'user']);

        $this->actingAs($user)->postJson('/api/reminders', [
            'time' => '08:00', 'days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'medicine_name' => 'Metformin', 'timezone' => 'Asia/Kolkata', 'pills_left' => 10, 'pills_per_dose' => 2,
        ])->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-11-03 09:00', 'Asia/Kolkata')); // two doses later
        $this->actingAs($user)->getJson('/api/reminders')
            ->assertJsonPath('data.0.pills_left', 6)
            ->assertJsonPath('data.0.days_left', 3);
    }

    public function test_refill_alert_goes_to_patient_and_family_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 09:00', 'Asia/Kolkata'));
        $patient = User::create(['mobile_number' => '9000000405', 'name' => 'Amma', 'role' => 'user', 'fcm_token' => 'p']);
        $son = User::create(['mobile_number' => '9000000406', 'name' => 'Ravi', 'role' => 'user', 'fcm_token' => 's']);
        CaregiverLink::create(['patient_id' => $patient->id, 'caregiver_id' => $son->id, 'status' => 'active']);
        $low = Reminder::create([
            'user_id' => $patient->id, 'time' => '08:00', 'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'is_active' => true, 'medicine_name' => 'Metformin', 'timezone' => 'Asia/Kolkata',
            'pills_left' => 3, 'pills_per_dose' => 1, 'pills_counted_at' => now(),
        ]);
        Reminder::create([
            'user_id' => $patient->id, 'time' => '20:00', 'days' => ['Mon'], 'is_active' => true,
            'medicine_name' => 'Plenty', 'timezone' => 'Asia/Kolkata', 'pills_left' => 30, 'pills_counted_at' => now(),
        ]);

        $this->artisan('reminders:check-refills')->assertSuccessful();
        $this->artisan('reminders:check-refills')->assertSuccessful();

        $this->assertCount(2, $this->pushes);
        $this->assertStringContainsString('Only 3 tablets of Metformin left', $this->pushes[0][1]);
        $this->assertSame('s', $this->pushes[1][0]);
        $this->assertSame(1, Notification::where('user_id', $patient->id)->where('type', 'refill')->count());

        // A new count after a refill allows the next alert.
        $this->actingAs($patient)->putJson("/api/reminders/{$low->id}", ['pills_left' => 2])->assertOk();
        $this->artisan('reminders:check-refills')->assertSuccessful();
        $this->assertCount(4, $this->pushes);
    }
}

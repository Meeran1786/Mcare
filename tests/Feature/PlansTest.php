<?php

namespace Tests\Feature;

use App\Models\CaregiverLink;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.razorpay.key_id' => 'rzp_test_key',
            'services.razorpay.key_secret' => 'test_secret',
            'services.razorpay.webhook_secret' => 'hook_secret',
        ]);
    }

    private function user(string $mobile, string $role = 'user'): User
    {
        return User::create(['mobile_number' => $mobile, 'role' => $role, 'status' => 'active', 'name' => 'Asha']);
    }

    private function signInOnWeb(string $mobile): void
    {
        DB::table('otps')->insert([
            'mobile_number' => $mobile, 'otp' => 4321,
            'expires_at' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->postJson('/plans/verify-otp', ['mobile_number' => $mobile, 'otp' => '4321'])->assertOk();
    }

    public function test_new_account_gets_a_trial_and_the_app_sees_no_prices(): void
    {
        $user = $this->user('9100000001');

        $res = $this->actingAs($user)->getJson('/api/plan')->assertOk();
        $res->assertJsonPath('data.plan', 'family')
            ->assertJsonPath('data.is_trial', true)
            ->assertJsonPath('data.family_members', 5);
        $this->assertArrayNotHasKey('price', $res->json('data'));
        $this->assertStringNotContainsString('₹', $res->getContent());

        // A second call does not start another trial.
        $this->actingAs($user)->getJson('/api/plan');
        $this->assertSame(1, Subscription::count());
    }

    public function test_trial_off_means_free(): void
    {
        config(['plans.trial_days' => 0]);
        $vendor = $this->user('9100000002', 'vendor');

        $this->actingAs($vendor)->getJson('/api/plan')
            ->assertJsonPath('data.plan', 'free')
            ->assertJsonPath('data.family_members', 1);
    }

    public function test_pages_razorpay_checks_are_public(): void
    {
        foreach (['/plans', '/refund-policy', '/shipping-policy', '/contact-us'] as $url) {
            $this->get($url)->assertOk()->assertSee('Refunds &amp; cancellation', false);
        }
        $this->get('/plans')->assertSee('₹121')->assertSee('₹499');
        $this->get('/pricing')->assertRedirect('/plans');
    }

    public function test_web_sign_in_only_for_existing_accounts(): void
    {
        config(['services.otp.demo_numbers' => ['9100000003']]);

        $this->postJson('/plans/send-otp', ['mobile_number' => '9100000013'])->assertStatus(404);
        $this->user('9100000003');
        $this->postJson('/plans/send-otp', ['mobile_number' => '9100000003'])->assertOk();
        $this->assertDatabaseHas('otps', ['mobile_number' => '9100000003']);
    }

    public function test_full_payment_flow_activates_the_plan_once(): void
    {
        config(['plans.trial_days' => 0]);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_ABC'], 200)]);
        $user = $this->user('9100000004');
        $this->signInOnWeb('9100000004');

        // Patients cannot buy the pharmacy plan.
        $this->postJson('/plans/order', ['plan' => 'pharmacy', 'period' => 'monthly'])->assertStatus(422);

        $this->postJson('/plans/order', ['plan' => 'family', 'period' => 'yearly'])
            ->assertOk()->assertJson(['order_id' => 'order_ABC', 'amount' => 99900]);
        Http::assertSent(fn ($r) => $r['amount'] === 99900 && $r['currency'] === 'INR');

        // A forged signature is refused.
        $this->postJson('/plans/confirm', [
            'razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => 'bad',
        ])->assertStatus(422);

        $sig = hash_hmac('sha256', 'order_ABC|pay_1', 'test_secret');
        $this->postJson('/plans/confirm', [
            'razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => $sig,
        ])->assertOk()->assertJsonPath('status.plan', 'family');

        // The webhook for the same payment adds nothing more.
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => [
            'id' => 'pay_1', 'order_id' => 'order_ABC', 'amount' => 99900,
        ]]]]);
        $this->call('POST', '/razorpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'hook_secret'),
        ], $body)->assertOk();

        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
        $sub = Subscription::first();
        $this->assertSame('yearly', $sub->kind);
        $this->assertEqualsWithDelta(365, now()->diffInDays($sub->ends_at), 1);
        $this->assertSame('paid', Payment::first()->status);
    }

    public function test_webhook_alone_activates_and_checks_signature(): void
    {
        $user = $this->user('9100000005');
        Payment::create([
            'user_id' => $user->id, 'plan' => 'family', 'period' => 'monthly',
            'amount' => 12100, 'razorpay_order_id' => 'order_W',
        ]);
        $body = json_encode(['event' => 'order.paid', 'payload' => ['payment' => ['entity' => [
            'id' => 'pay_W', 'order_id' => 'order_W', 'amount' => 12100,
        ]]]]);

        $this->call('POST', '/razorpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => 'forged',
        ], $body)->assertStatus(400);
        $this->assertSame('created', Payment::first()->status);

        $this->call('POST', '/razorpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'hook_secret'),
        ], $body)->assertOk();
        $this->assertSame('paid', Payment::first()->status);
        $this->assertSame('family', Subscription::where('kind', 'monthly')->first()->plan);
    }

    public function test_paying_again_adds_time_after_the_current_period(): void
    {
        $user = $this->user('9100000006');
        $plans = app(\App\Services\PlanService::class);
        $first = $plans->addTime($user, 'family', 'monthly', 30);
        $second = $plans->addTime($user, 'family', 'monthly', 30);

        $this->assertTrue($second->starts_at->equalTo($first->ends_at));
    }

    public function test_free_patients_get_one_family_member(): void
    {
        config(['plans.trial_days' => 0]);
        $patient = $this->user('9100000007');
        $a = $this->user('9100000008');
        $b = $this->user('9100000009');

        foreach ([[$a, 200], [$b, 422]] as [$member, $status]) {
            $code = $this->actingAs($patient)->postJson('/api/caregivers/invite')->json('data.code');
            $this->actingAs($member)->postJson('/api/caregivers/accept', ['code' => $code])->assertStatus($status);
        }

        app(\App\Services\PlanService::class)->addTime($patient, 'family', 'granted', 30);
        $code = $this->actingAs($patient)->postJson('/api/caregivers/invite')->json('data.code');
        $this->actingAs($b)->postJson('/api/caregivers/accept', ['code' => $code])->assertOk();
        $this->assertSame(2, CaregiverLink::active()->where('patient_id', $patient->id)->count());
    }

    public function test_grant_command(): void
    {
        $vendor = $this->user('9100000010', 'vendor');
        $this->artisan('plans:grant', ['mobile' => '9100000010', 'days' => 60])->assertSuccessful();
        $this->assertSame('pharmacy', Subscription::where('user_id', $vendor->id)->first()->plan);
    }
}

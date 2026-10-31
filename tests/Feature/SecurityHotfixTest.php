<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Prescription;
use App\Models\PrescriptionFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHotfixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.two_factor.api_key' => 'test-key',
            'services.two_factor.otp_template' => 'OTP1',
        ]);

        Http::fake(['2factor.in/*' => Http::response(['Status' => 'Success'])]);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function makeUser(string $mobile, string $role = 'user'): User
    {
        return User::create([
            'mobile_number' => $mobile,
            'role' => $role,
            'status' => 'active',
            'preferred_language' => 'en',
        ]);
    }

    private function makePrescription(User $owner, string $path = 'prescriptions/original.jpg'): Prescription
    {
        $prescription = Prescription::create([
            'user_id' => $owner->id,
            'latitude' => 17.38,
            'longitude' => 78.48,
            'status' => 'sent',
        ]);

        Storage::disk('local')->put($path, 'original-image');
        $prescription->files()->create(['file_path' => $path]);

        return $prescription;
    }

    /* ---------------- 1. clear-database ---------------- */

    public function test_clear_database_endpoint_is_gone(): void
    {
        $user = $this->makeUser('9000000100');

        $this->postJson('/api/clear-database', ['key' => '123456'])->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /* ---------------- 2. OTP logging and rate limits ---------------- */

    public function test_otp_is_never_written_to_the_log(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        $this->postJson('/api/auth/send-otp', ['mobile_number' => '9000000101', 'role' => 'user'])
            ->assertOk();

        $otp = (string) DB::table('otps')->where('mobile_number', '9000000101')->value('otp');
        $this->assertSame(4, strlen($otp));

        foreach ($logged as $line) {
            $this->assertStringNotContainsString($otp, $line);
        }
    }

    public function test_send_otp_is_rate_limited_per_number(): void
    {
        $payload = ['mobile_number' => '9000000102', 'role' => 'user'];

        $this->postJson('/api/auth/send-otp', $payload)->assertOk();
        $this->postJson('/api/auth/send-otp', $payload)
            ->assertStatus(429)
            ->assertJson(['success' => false, 'message_key' => 'tooManyOtpRequests']);

        // A different number is not blocked.
        $this->postJson('/api/auth/send-otp', ['mobile_number' => '9000000103', 'role' => 'user'])
            ->assertOk();
    }

    public function test_otp_is_thrown_away_after_too_many_wrong_codes(): void
    {
        DB::table('otps')->insert([
            'mobile_number' => '9000000104',
            'otp' => '4321',
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wrong = ['mobile_number' => '9000000104', 'otp' => '0000', 'role' => 'user'];

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/auth/verify-otp', $wrong)->assertStatus(401);
        }

        $this->postJson('/api/auth/verify-otp', $wrong)
            ->assertStatus(429)
            ->assertJson(['message_key' => 'tooManyOtpAttempts']);

        $this->assertDatabaseMissing('otps', ['mobile_number' => '9000000104']);

        // Even the right code no longer works; the user must request a new OTP.
        $this->postJson('/api/auth/verify-otp', ['mobile_number' => '9000000104', 'otp' => '4321', 'role' => 'user'])
            ->assertStatus(429);
    }

    /* ---------------- 3. prescription IDOR ---------------- */

    public function test_user_cannot_replace_another_users_prescription(): void
    {
        $owner = $this->makeUser('9000000105');
        $attacker = $this->makeUser('9000000106');
        $prescription = $this->makePrescription($owner);

        Sanctum::actingAs($attacker);

        $this->post('/api/user/update-prescription', [
            'prescription_id' => $prescription->id,
            'prescription' => UploadedFile::fake()->image('evil.jpg'),
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertDatabaseHas('prescription_files', [
            'prescription_id' => $prescription->id,
            'file_path' => 'prescriptions/original.jpg',
        ]);
    }

    public function test_user_cannot_replace_prescription_through_another_users_order(): void
    {
        $owner = $this->makeUser('9000000107');
        $attacker = $this->makeUser('9000000108');
        $vendor = $this->makeUser('9000000109', 'vendor');
        $prescription = $this->makePrescription($owner);

        $pharmacyId = DB::table('pharmacies')->insertGetId([
            'user_id' => $vendor->id,
            'pharmacy_name' => 'Test Pharmacy',
            'license_number' => 'LIC-1',
            'address_line' => '1 Main Road',
            'area' => 'Center',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500001',
            'latitude' => 17.38,
            'longitude' => 78.48,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::create([
            'user_id' => $owner->id,
            'pharmacy_id' => $pharmacyId,
            'prescription_id' => $prescription->id,
            'delivery_lat' => 17.38,
            'delivery_lng' => 78.48,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($attacker);

        $this->post('/api/user/update-prescription', [
            'order_id' => $order->id,
            'prescription' => UploadedFile::fake()->image('evil.jpg'),
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertDatabaseHas('prescription_files', ['file_path' => 'prescriptions/original.jpg']);
    }

    public function test_owner_can_replace_their_prescription_and_it_is_stored_privately(): void
    {
        $owner = $this->makeUser('9000000110');
        $prescription = $this->makePrescription($owner);

        Sanctum::actingAs($owner);

        $response = $this->post('/api/user/update-prescription', [
            'prescription_id' => $prescription->id,
            'prescription' => UploadedFile::fake()->image('new.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $path = $prescription->files()->first()->file_path;

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringContainsString('signature=', $response->json('image'));
    }

    /* ---------------- 4. private prescription images ---------------- */

    public function test_prescription_image_needs_a_valid_signed_link(): void
    {
        $owner = $this->makeUser('9000000111');
        $file = $this->makePrescription($owner)->files()->first();

        // The bare path, which used to be public, is refused.
        $this->get('/storage/prescriptions/original.jpg')->assertForbidden();

        // A tampered signature is refused.
        $this->get(str_replace('signature=', 'signature=x', $file->url))->assertForbidden();

        // The signed link works.
        $link = $file->url;
        $this->get($link)->assertOk();

        // The link expires.
        $this->travel(PrescriptionFile::LINK_MINUTES + 1)->minutes();
        $this->get($link)->assertForbidden();
    }

    public function test_api_returns_signed_file_path_that_old_apps_can_prefix_with_storage(): void
    {
        $owner = $this->makeUser('9000000112');
        $file = $this->makePrescription($owner)->files()->first();

        $filePath = $file->toArray()['file_path'];

        $this->assertStringStartsWith('prescriptions/original.jpg?', $filePath);
        $this->assertStringContainsString('signature=', $filePath);

        // This is how the live apps build the image URL.
        $this->get('/storage/'.$filePath)->assertOk();
    }

    public function test_command_moves_existing_public_images_to_private_storage(): void
    {
        Storage::disk('public')->put('prescriptions/legacy.jpg', 'legacy-image');

        $this->artisan('prescriptions:make-private')->assertSuccessful();

        Storage::disk('public')->assertMissing('prescriptions/legacy.jpg');
        Storage::disk('local')->assertExists('prescriptions/legacy.jpg');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    private function signUp(string $mobile, string $role): string
    {
        DB::table('otps')->insert([
            'mobile_number' => $mobile,
            'otp' => 4321,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'mobile_number' => $mobile,
            'otp' => '4321',
            'role' => $role,
        ]);

        $response->assertOk()->assertJson(['success' => true, 'is_new_user' => true]);

        return $response->json('token');
    }

    public function test_new_user_can_sign_up_and_save_fcm_token(): void
    {
        $token = $this->signUp('9000000001', 'user');

        $this->withToken($token)
            ->postJson('/api/save-fcm-token', ['fcm_token' => 'device-token-1'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'mobile_number' => '9000000001',
            'fcm_token' => 'device-token-1',
        ]);
    }

    public function test_new_vendor_can_sign_up_and_register_pharmacy(): void
    {
        $token = $this->signUp('9000000002', 'vendor');

        $this->withToken($token)
            ->postJson('/api/vendor/pharmacy', [
                'owner_name' => 'Owner',
                'pharmacy_name' => 'Test Pharmacy',
                'license_number' => 'LIC-1',
                'registration_number' => 'REG-1',
                'address_line' => '1 Main Road',
                'area' => 'Center',
                'landmark' => 'Near park',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'pincode' => '500001',
                'latitude' => 17.38,
                'longitude' => 78.48,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('pharmacies', [
            'owner_name' => 'Owner',
            'registration_number' => 'REG-1',
            'landmark' => 'Near park',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|digits_between:10,15',
            'role' => 'required|in:user,vendor'
        ]);

        $user = User::where('mobile_number', $request->mobile_number)->first();

        /*
        |--------------------------------------------------------------------------
        | Role Validation Logic (STRICT)
        |--------------------------------------------------------------------------
        */

        /*
        Vendor app rule
        */
        if ($request->role === 'vendor') {

            // If number already registered as USER → block
            if ($user && $user->role === 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'This number is already registered as a user.',
                    'message_key' => 'alreadyRegisteredAsUser'
                ], 403);
            }

        }

        /*
        User app rule
        */
        if ($request->role === 'user') {

            // If number already registered as VENDOR → block
            if ($user && $user->role === 'vendor') {
                return response()->json([
                    'success' => false,
                    'message' => 'This number is already registered as a vendor.',
                    'message_key' => 'alreadyRegisteredAsVendor'
                ], 403);
            }

        }

        $demoNumbers = ['9955995599', '9911991199'];
        $otp = in_array($request->mobile_number, $demoNumbers, true)
            ? 1234
            : rand(1000, 9999);

        DB::table('otps')->updateOrInsert(
            ['mobile_number' => $request->mobile_number],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        logger("OTP for {$request->mobile_number} is {$otp}");

        if (!in_array($request->mobile_number, $demoNumbers, true)) {
            $apiKey = config('services.two_factor.api_key');
            $templateName = config('services.two_factor.otp_template');

            if (!$apiKey || !$templateName) {
                logger()->error('2Factor SMS configuration is missing.');

                return response()->json([
                    'success' => false,
                    'message' => 'OTP SMS service is not configured',
                    'message_key' => 'otpSmsNotConfigured'
                ], 500);
            }

            try {
                $response = Http::timeout(10)->post(
                    "https://2factor.in/API/V1/{$apiKey}/SMS/{$request->mobile_number}/{$otp}/{$templateName}"
                );
            } catch (\Throwable $e) {
                logger()->error('2Factor OTP SMS request failed.', [
                    'mobile_number' => $request->mobile_number,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP SMS',
                    'message_key' => 'otpSmsFailed'
                ], 502);
            }

            if (!$response->successful() || data_get($response->json(), 'Status') !== 'Success') {
                logger()->error('2Factor OTP SMS failed.', [
                    'mobile_number' => $request->mobile_number,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP SMS',
                    'message_key' => 'otpSmsFailed'
                ], 502);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'message_key' => 'otpSent',
            'is_existing_user' => $user ? true : false
        ]);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|digits_between:10,15',
            'otp' => 'required|digits:4',
            'role' => 'required|in:user,vendor'
        ]);

        $otpRecord = DB::table('otps')
            ->where('mobile_number', $request->mobile_number)
            ->where('otp', $request->otp)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
                'message_key' => 'invalidOtp'
            ], 401);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired',
                'message_key' => 'otpExpired'
            ], 401);
        }

        $user = User::where('mobile_number', $request->mobile_number)->first();
        $isNewUser = false;

        if (!$user) {

            $user = User::create([
                'mobile_number' => $request->mobile_number,
                'role' => $request->role,
                'status' => 'active',
                'preferred_language' => 'en',
            ]);

            $isNewUser = true;

        }

        DB::table('otps')
            ->where('mobile_number', $request->mobile_number)
            ->delete();

        $token = $user->createToken('otp-login')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'is_new_user' => $isNewUser,
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
                'name' => $user->name,
                'mobile_number' => $user->mobile_number,
                'preferred_language' => $user->preferred_language,
            ],
        ]);
    }
   public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

   public function saveFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string'
    ]);

    $user = $request->user(); // 🔥 logged-in user

    $user->update([
        'fcm_token' => $request->fcm_token
    ]);

    try {
        \App\Models\DeviceToken::updateOrCreate(
            ['token' => $request->fcm_token],
            [
                'user_id' => $user->id,
                'platform' => $request->input('platform'),
                'last_seen_at' => now(),
            ]
        );
    } catch (\Throwable $e) {
        \Log::warning('Device token history not saved', [
            'user_id' => $user->id,
            'message' => $e->getMessage(),
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'FCM token saved successfully'
    ]);
}

}

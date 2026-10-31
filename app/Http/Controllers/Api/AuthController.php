<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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

        $demoNumbers = config('services.otp.demo_numbers', []);
        $otp = in_array($request->mobile_number, $demoNumbers, true)
            ? 1234
            : random_int(1000, 9999);

        DB::table('otps')->updateOrInsert(
            ['mobile_number' => $request->mobile_number],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // A fresh code gets a fresh set of verify attempts. Never log the code itself.
        RateLimiter::clear($this->verifyAttemptsKey($request->mobile_number));

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
                // The exception message contains the request URL, which holds the
                // API key and the OTP, so only the exception type is logged.
                logger()->error('2Factor OTP SMS request failed.', [
                    'exception' => get_class($e),
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

        $attemptsKey = $this->verifyAttemptsKey($request->mobile_number);
        $maxAttempts = max(1, (int) config('services.otp.max_verify_attempts', 5));

        if (RateLimiter::tooManyAttempts($attemptsKey, $maxAttempts)) {
            return $this->tooManyVerifyAttempts($request->mobile_number);
        }

        $storedOtp = DB::table('otps')
            ->where('mobile_number', $request->mobile_number)
            ->first();

        if (!$storedOtp || !hash_equals((string) $storedOtp->otp, (string) $request->otp)) {
            RateLimiter::hit($attemptsKey, 900);

            if ($storedOtp && RateLimiter::tooManyAttempts($attemptsKey, $maxAttempts)) {
                return $this->tooManyVerifyAttempts($request->mobile_number);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
                'message_key' => 'invalidOtp'
            ], 401);
        }

        $otpRecord = $storedOtp;

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

        RateLimiter::clear($attemptsKey);

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
    private function verifyAttemptsKey(string $mobileNumber): string
    {
        return 'otp-verify-attempts:'.$mobileNumber;
    }

    /**
     * Too many wrong codes: throw the OTP away so it cannot be guessed,
     * and make the user request a new one.
     */
    private function tooManyVerifyAttempts(string $mobileNumber)
    {
        DB::table('otps')->where('mobile_number', $mobileNumber)->delete();

        return response()->json([
            'success' => false,
            'message' => 'Too many wrong attempts. Please request a new OTP.',
            'message_key' => 'tooManyOtpAttempts'
        ], 429);
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

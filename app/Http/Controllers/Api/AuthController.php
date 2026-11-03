<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Services\OtpService;
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

        $error = app(OtpService::class)->send($request->mobile_number);

        if ($error) {
            [$message, $key, $status] = $error;

            return response()->json([
                'success' => false,
                'message' => $message,
                'message_key' => $key
            ], $status);
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

        $result = app(OtpService::class)->check($request->mobile_number, (string) $request->otp);

        if ($result === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Too many wrong attempts. Please request a new OTP.',
                'message_key' => 'tooManyOtpAttempts'
            ], 429);
        }

        if ($result === 'invalid') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
                'message_key' => 'invalidOtp'
            ], 401);
        }

        if ($result === 'expired') {
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
        'fcm_token' => 'required|string',
        'local_reminders' => 'sometimes|boolean',
    ]);

    $user = $request->user(); // 🔥 logged-in user

    $user->update([
        'fcm_token' => $request->fcm_token,
        // The 2.0 app rings reminders itself. Older apps don't send this,
        // so signing in on an old app turns the server push back on.
        'local_reminders' => $request->boolean('local_reminders'),
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

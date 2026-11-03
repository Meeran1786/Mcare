<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Phone OTP, shared by the apps (Api\AuthController) and the website plans
 * page. Never log the code itself.
 */
class OtpService
{
    /** Stores and texts a new code. Returns null on success, or [message, key, http status]. */
    public function send(string $mobile): ?array
    {
        $demoNumbers = config('services.otp.demo_numbers', []);
        $isDemo = in_array($mobile, $demoNumbers, true);
        $otp = $isDemo ? 1234 : random_int(1000, 9999);

        DB::table('otps')->updateOrInsert(
            ['mobile_number' => $mobile],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // A fresh code gets a fresh set of verify attempts.
        RateLimiter::clear($this->attemptsKey($mobile));

        if ($isDemo) {
            return null;
        }

        $apiKey = config('services.two_factor.api_key');
        $templateName = config('services.two_factor.otp_template');

        if (!$apiKey || !$templateName) {
            logger()->error('2Factor SMS configuration is missing.');

            return ['OTP SMS service is not configured', 'otpSmsNotConfigured', 500];
        }

        try {
            $response = Http::timeout(10)->post(
                "https://2factor.in/API/V1/{$apiKey}/SMS/{$mobile}/{$otp}/{$templateName}"
            );
        } catch (\Throwable $e) {
            // The exception message contains the request URL, which holds the
            // API key and the OTP, so only the exception type is logged.
            logger()->error('2Factor OTP SMS request failed.', ['exception' => get_class($e)]);

            return ['Failed to send OTP SMS', 'otpSmsFailed', 502];
        }

        if (!$response->successful() || data_get($response->json(), 'Status') !== 'Success') {
            logger()->error('2Factor OTP SMS failed.', [
                'mobile_number' => $mobile,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return ['Failed to send OTP SMS', 'otpSmsFailed', 502];
        }

        return null;
    }

    /** Returns 'ok', 'invalid', 'expired' or 'locked'. A good code is used up. */
    public function check(string $mobile, string $code): string
    {
        $attemptsKey = $this->attemptsKey($mobile);
        $maxAttempts = max(1, (int) config('services.otp.max_verify_attempts', 5));

        if (RateLimiter::tooManyAttempts($attemptsKey, $maxAttempts)) {
            return $this->lock($mobile);
        }

        $stored = DB::table('otps')->where('mobile_number', $mobile)->first();

        if (!$stored || !hash_equals((string) $stored->otp, (string) $code)) {
            RateLimiter::hit($attemptsKey, 900);

            if ($stored && RateLimiter::tooManyAttempts($attemptsKey, $maxAttempts)) {
                return $this->lock($mobile);
            }

            return 'invalid';
        }

        if (Carbon::now()->greaterThan($stored->expires_at)) {
            return 'expired';
        }

        DB::table('otps')->where('mobile_number', $mobile)->delete();
        RateLimiter::clear($attemptsKey);

        return 'ok';
    }

    /** Too many wrong codes: throw the OTP away so it cannot be guessed. */
    private function lock(string $mobile): string
    {
        DB::table('otps')->where('mobile_number', $mobile)->delete();

        return 'locked';
    }

    private function attemptsKey(string $mobile): string
    {
        return 'otp-verify-attempts:'.$mobile;
    }
}

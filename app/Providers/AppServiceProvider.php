<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://') || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        $this->configureOtpRateLimits();
    }

    /**
     * OTP endpoints are public, so they are throttled per phone number and per IP.
     * This stops SMS bombing of a number and brute forcing of the 4-digit code.
     */
    private function configureOtpRateLimits(): void
    {
        $tooMany = fn (Request $request, array $headers) => response()->json([
            'success' => false,
            'message' => 'Too many attempts. Please wait and try again.',
            'message_key' => 'tooManyOtpRequests',
        ], 429, $headers);

        RateLimiter::for('otp-send', function (Request $request) use ($tooMany) {
            $mobile = (string) $request->input('mobile_number');

            return [
                Limit::perMinute(1)->by('otp-send-minute:'.$mobile)->response($tooMany),
                Limit::perHour(5)->by('otp-send-hour:'.$mobile)->response($tooMany),
                Limit::perHour(30)->by('otp-send-ip:'.$request->ip())->response($tooMany),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) use ($tooMany) {
            $mobile = (string) $request->input('mobile_number');

            return [
                Limit::perHour(20)->by('otp-verify-number:'.$mobile)->response($tooMany),
                Limit::perMinute(30)->by('otp-verify-ip:'.$request->ip())->response($tooMany),
            ];
        });
    }
}

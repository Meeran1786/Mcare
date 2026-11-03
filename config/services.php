<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    // iPhone pushes go straight to Apple. The .p8 key is a secret: keep it in
    // storage/app/apns.p8 (git-ignored), never in the repository.
    'apns' => [
        'key_path' => env('APNS_KEY_PATH', storage_path('app/apns.p8')),
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID'),
        'patient_bundle' => env('APNS_PATIENT_BUNDLE', 'com.Mcare'),
        'partner_bundle' => env('APNS_PARTNER_BUNDLE', 'com.mcarepartner'),
    ],
    
    // Website payments for plans. The key secret and webhook secret live only
    // in the server .env, never in the repository or in chat.
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'two_factor' => [
        'api_key' => env('TWO_FACTOR_API_KEY'),
        'otp_template' => env('TWO_FACTOR_OTP_TEMPLATE', 'OTP1'),
    ],

    'otp' => [
        // Store-review demo logins that always use OTP 1234 and never get an SMS.
        // Set OTP_DEMO_NUMBERS= (empty) in production once store review is done.
        'demo_numbers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OTP_DEMO_NUMBERS', '9955995599,9911991199'))
        ))),
        // Wrong codes allowed for one OTP before it is thrown away.
        'max_verify_attempts' => (int) env('OTP_MAX_VERIFY_ATTEMPTS', 5),
    ],

];

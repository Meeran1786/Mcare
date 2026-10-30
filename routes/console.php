<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Google\Client;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    \Log::info('TASK SCHEDULER WORKING');
})->everyMinute();

Schedule::command('reminder:send --grace=10')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('firebase:check', function () {
    $credentialsPath = storage_path('app/firebase.json');
    $projectId = config('services.firebase.project_id');

    $this->line('Project ID: ' . ($projectId ?: 'NOT SET'));
    $this->line('Credentials: ' . $credentialsPath);

    if (!is_file($credentialsPath)) {
        $this->error('firebase.json not found.');
        return self::FAILURE;
    }

    $json = json_decode(file_get_contents($credentialsPath), true) ?: [];
    $this->line('Service account: ' . ($json['client_email'] ?? 'NOT FOUND'));
    $this->line('Credential project: ' . ($json['project_id'] ?? 'NOT FOUND'));

    if (!empty($projectId) && !empty($json['project_id']) && $json['project_id'] !== $projectId) {
        $this->error("Project mismatch: FIREBASE_PROJECT_ID is {$projectId}, but firebase.json is for {$json['project_id']}.");
        $this->error('Replace storage/app/firebase.json with a Firebase Admin SDK key from the configured project.');
        return self::FAILURE;
    }

    try {
        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        if (!empty($token['access_token'])) {
            $this->info('Firebase access token OK.');
            return self::SUCCESS;
        }

        $this->error('Firebase access token failed: ' . json_encode($token));
        return self::FAILURE;
    } catch (\Throwable $e) {
        $this->error('Firebase access token exception: ' . $e->getMessage());
        return self::FAILURE;
    }
})->purpose('Verify Firebase service-account credentials for FCM');

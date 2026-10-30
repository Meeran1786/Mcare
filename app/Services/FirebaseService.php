<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private ?string $accessToken = null;

    public function sendNotification($fcmToken, $title, $message, $extraData = [])
    {
        if (!$fcmToken) {
            return false;
        }

        $credentialsPath = storage_path('app/firebase.json');
        $projectId = config('services.firebase.project_id');

        if (!$projectId || !is_file($credentialsPath)) {
            Log::error('FCM configuration missing', [
                'project_id_configured' => (bool) $projectId,
                'credentials_path' => $credentialsPath,
                'credentials_found' => is_file($credentialsPath),
            ]);
            return false;
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true) ?: [];
        $credentialsProjectId = $credentials['project_id'] ?? null;

        if ($credentialsProjectId && $credentialsProjectId !== $projectId) {
            Log::error('FCM project mismatch', [
                'configured_project_id' => $projectId,
                'credentials_project_id' => $credentialsProjectId,
                'credentials_client_email' => $credentials['client_email'] ?? null,
                'fix' => 'Use a Firebase Admin SDK service-account key from the same project as FIREBASE_PROJECT_ID.',
            ]);
            return false;
        }

        $accessToken = $this->getAccessToken($credentialsPath);

        if (!$accessToken) {
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $imageUrl = $extraData['image_url'] ?? $extraData['image'] ?? null;
        $channelId = $extraData['channel_id'] ?? 'app_channel_v2';
        $sound = $this->normalizeSoundName($extraData['sound'] ?? 'app_push_sound');
        $clickAction = $extraData['click_action'] ?? 'OPEN_MCARE_APP';

        $data = array_map(static fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), array_merge([
            'title' => $title,
            'body' => $message,
            'sound' => $sound,
            'channel_id' => $channelId,
            'click_action' => $clickAction,
        ], array_filter($extraData, fn ($value) => $value !== null)));

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'HIGH',
                    'ttl' => '3600s',
                    'notification' => [
                        'channel_id' => $channelId,
                        'sound' => $sound,
                        'default_sound' => $sound === 'default',
                        'default_vibrate_timings' => true,
                        'visibility' => 'PUBLIC',
                        'notification_priority' => 'PRIORITY_HIGH',
                        'click_action' => $clickAction,
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => $sound === 'default' ? 'default' : "{$sound}.wav",
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        if ($imageUrl) {
            $payload['message']['notification']['image'] = $imageUrl;
            $payload['message']['android']['notification']['image'] = $imageUrl;
            $payload['message']['apns']['fcm_options']['image'] = $imageUrl;
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$accessToken}",
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 20,
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } catch (\Throwable $e) {
            Log::error('FCM send exception', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);
            return false;
        }

        Log::info('FCM response', [
            'status' => $status,
            'error' => $error,
            'response' => $response,
            'data' => $data,
        ]);

        return !$error && $status >= 200 && $status < 300;
    }

    private function normalizeSoundName(string $sound): string
    {
        if ($sound === 'default') {
            return $sound;
        }

        return preg_replace('/\.(wav|mp3|ogg)$/i', '', $sound) ?: 'app_push_sound';
    }

    private function getAccessToken(string $credentialsPath): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $tokenResponse = $client->fetchAccessTokenWithAssertion();
            $accessToken = $tokenResponse['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FCM access token exception', [
                'message' => $e->getMessage(),
                'fix' => 'Replace storage/app/firebase.json with a valid Firebase Admin SDK service-account key for this project.',
            ]);
            return null;
        }

        if (empty($accessToken)) {
            Log::error('FCM access token failed', [
                'response' => $tokenResponse ?? null,
                'fix' => 'Replace storage/app/firebase.json with a valid Firebase Admin SDK service-account key for this project.',
            ]);
            return null;
        }

        $this->accessToken = $accessToken;

        return $accessToken;
    }
}


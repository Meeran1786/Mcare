<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications to iPhones through Apple's push service (APNs),
 * using a token-based .p8 key from the Apple Developer account.
 *
 * The 2.0 apps register the phone's own Apple token (64 hex characters) with
 * /save-fcm-token. FirebaseService hands those tokens here, so every place
 * that sends a push works for iPhones without changes.
 */
class ApnsService
{
    private const PRODUCTION = 'https://api.push.apple.com';
    private const SANDBOX = 'https://api.sandbox.push.apple.com';

    private ?string $jwt = null;
    private int $jwtIssuedAt = 0;

    public static function isApnsToken(?string $token): bool
    {
        return is_string($token) && preg_match('/^[0-9a-f]{64}$/i', $token) === 1;
    }

    public function send(string $token, string $title, string $message, array $extraData = []): bool
    {
        $jwt = $this->jwt();
        if (!$jwt) {
            return false;
        }

        $data = array_map(
            static fn ($v) => is_scalar($v) ? (string) $v : json_encode($v),
            array_filter($extraData, fn ($v) => $v !== null)
        );

        $payload = array_merge($data, [
            'aps' => [
                'alert' => ['title' => $title, 'body' => $message],
                'sound' => 'default',
                'badge' => 1,
            ],
            // expo-notifications reads custom data from "body".
            'body' => $data,
        ]);

        $headers = [
            'authorization' => "bearer {$jwt}",
            'apns-topic' => $this->topicFor($token),
            'apns-push-type' => 'alert',
            'apns-priority' => '10',
        ];

        $response = $this->post(self::PRODUCTION, $token, $headers, $payload);

        // Development builds get sandbox tokens, which production rejects.
        if ($response && $response->status() === 400 && $response->json('reason') === 'BadDeviceToken') {
            $response = $this->post(self::SANDBOX, $token, $headers, $payload);
        }

        $ok = $response && $response->successful();

        Log::info('APNs response', [
            'status' => $response?->status(),
            'reason' => $response?->json('reason'),
            'topic' => $headers['apns-topic'],
        ]);

        return $ok;
    }

    private function post(string $host, string $token, array $headers, array $payload)
    {
        try {
            return Http::withHeaders($headers)
                ->withOptions(['version' => 2.0])
                ->timeout(20)
                ->post("{$host}/3/device/{$token}", $payload);
        } catch (\Throwable $e) {
            Log::error('APNs send exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /** Pharmacy accounts use the Partner app; everyone else the patient app. */
    private function topicFor(string $token): string
    {
        $role = User::where('fcm_token', $token)->value('role');

        return $role === 'vendor'
            ? config('services.apns.partner_bundle')
            : config('services.apns.patient_bundle');
    }

    /** Apple accepts a signed token for up to an hour; make a new one every 50 minutes. */
    private function jwt(): ?string
    {
        if ($this->jwt && time() - $this->jwtIssuedAt < 3000) {
            return $this->jwt;
        }

        $keyPath = config('services.apns.key_path');
        $keyId = config('services.apns.key_id');
        $teamId = config('services.apns.team_id');

        if (!$keyId || !$teamId || !is_file($keyPath)) {
            Log::error('APNs configuration missing', [
                'key_id_configured' => (bool) $keyId,
                'team_id_configured' => (bool) $teamId,
                'key_found' => is_file((string) $keyPath),
            ]);
            return null;
        }

        $key = openssl_pkey_get_private((string) file_get_contents($keyPath));
        if (!$key) {
            Log::error('APNs key could not be read', ['key_path' => $keyPath]);
            return null;
        }

        $now = time();
        $segments = [
            $this->base64url(json_encode(['alg' => 'ES256', 'kid' => $keyId])),
            $this->base64url(json_encode(['iss' => $teamId, 'iat' => $now])),
        ];
        $input = implode('.', $segments);

        if (!openssl_sign($input, $der, $key, OPENSSL_ALGO_SHA256)) {
            Log::error('APNs token signing failed');
            return null;
        }

        $this->jwt = $input . '.' . $this->base64url($this->derToRaw($der));
        $this->jwtIssuedAt = $now;

        return $this->jwt;
    }

    private function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** openssl gives a DER signature; JWT wants the two 32-byte numbers side by side. */
    private function derToRaw(string $der): string
    {
        $offset = 3 + (ord($der[1]) & 0x80 ? 1 : 0);
        $rLength = ord($der[$offset]);
        $r = substr($der, $offset + 1, $rLength);
        $offset += 1 + $rLength + 1;
        $sLength = ord($der[$offset]);
        $s = substr($der, $offset + 1, $sLength);

        $pad = static fn ($n) => str_pad(ltrim($n, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $pad($r) . $pad($s);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApnsTest extends TestCase
{
    use RefreshDatabase;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($key, $pem);
        $this->publicKey = openssl_pkey_get_details($key)['key'];
        $path = tempnam(sys_get_temp_dir(), 'apns');
        file_put_contents($path, $pem);
        config([
            'services.apns.key_path' => $path,
            'services.apns.key_id' => 'KEY123',
            'services.apns.team_id' => 'TEAM123',
        ]);
    }

    private function token(string $c): string
    {
        return str_repeat($c, 64);
    }

    public function test_iphone_token_goes_to_apple_with_a_valid_signed_token(): void
    {
        Http::fake(['api.push.apple.com/*' => Http::response('', 200)]);
        $token = $this->token('a');
        User::create(['mobile_number' => '9000000301', 'role' => 'user', 'fcm_token' => $token]);

        $ok = app(FirebaseService::class)->sendNotification($token, 'Missed dose', 'Amma has not taken Metformin', [
            'type' => 'MISSED_DOSE',
            'patient_id' => 5,
        ]);

        $this->assertTrue($ok);
        Http::assertSent(function (Request $r) use ($token) {
            [$head, $claims, $sig] = explode('.', substr($r->header('authorization')[0], 7));
            $raw = base64_decode(strtr($sig, '-_', '+/'));
            $this->assertSame(64, strlen($raw));
            $der = $this->rawToDer($raw);
            $this->assertSame(1, openssl_verify("$head.$claims", $der, $this->publicKey, OPENSSL_ALGO_SHA256));

            return $r->url() === "https://api.push.apple.com/3/device/{$token}"
                && $r->header('apns-topic')[0] === 'com.Mcare'
                && $r['aps']['alert']['title'] === 'Missed dose'
                && $r['body']['patient_id'] === '5'
                && $r['type'] === 'MISSED_DOSE';
        });
    }

    public function test_pharmacy_token_uses_partner_app_and_falls_back_to_sandbox(): void
    {
        Http::fake([
            'api.push.apple.com/*' => Http::response(['reason' => 'BadDeviceToken'], 400),
            'api.sandbox.push.apple.com/*' => Http::response('', 200),
        ]);
        $token = $this->token('b');
        User::create(['mobile_number' => '9000000302', 'role' => 'vendor', 'fcm_token' => $token]);

        $this->assertTrue(app(FirebaseService::class)->sendNotification($token, 'New prescription', 'Open the app'));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://api.sandbox.push.apple.com/')
            && $r->header('apns-topic')[0] === 'com.mcarepartner');
    }

    public function test_android_tokens_are_not_sent_to_apple(): void
    {
        Http::fake();
        $this->assertFalse(\App\Services\ApnsService::isApnsToken('fcm:APA91bHPRgkF3JUikC4ENAHEeMrd41Zxv3hVZjC9KtT8OvPVGJ'));
        $this->assertTrue(\App\Services\ApnsService::isApnsToken($this->token('c')));
    }

    private function rawToDer(string $raw): string
    {
        $int = function (string $n) {
            $n = ltrim($n, "\x00");
            if (ord($n[0]) & 0x80) {
                $n = "\x00" . $n;
            }
            return "\x02" . chr(strlen($n)) . $n;
        };
        $seq = $int(substr($raw, 0, 32)) . $int(substr($raw, 32));
        return "\x30" . chr(strlen($seq)) . $seq;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RazorpayService
{
    public function configured(): bool
    {
        return (bool) (config('services.razorpay.key_id') && config('services.razorpay.key_secret'));
    }

    /** Creates a Razorpay order and returns its id. Amount is in paise. */
    public function createOrder(int $amount, string $receipt, array $notes = []): string
    {
        $response = Http::timeout(15)
            ->withBasicAuth(config('services.razorpay.key_id'), config('services.razorpay.key_secret'))
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => $receipt,
                'notes' => $notes,
            ]);

        if (!$response->successful() || !$response->json('id')) {
            logger()->error('Razorpay order failed.', ['status' => $response->status(), 'error' => $response->json('error')]);
            throw new RuntimeException('Could not start the payment.');
        }

        return $response->json('id');
    }

    /** Checks the signature Razorpay Checkout returns after a payment. */
    public function validPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = (string) config('services.razorpay.key_secret');
        if ($secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $orderId.'|'.$paymentId, $secret), $signature);
    }

    public function validWebhookSignature(string $body, string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the PayPal REST API (Orders v2 + OAuth2 + webhook
 * signature verification). No SDK dependency — just Laravel's HTTP client.
 *
 * Unlike AddressValidationService this does NOT fail open: a broken PayPal
 * call must surface as an error to the caller, never be swallowed, since it
 * guards real money moving.
 */
class PayPalClient
{
    /** @return array<string, mixed> */
    public function createOrder(int $amountCents, string $currency, string $referenceId): array
    {
        return $this->request('post', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $referenceId,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amountCents / 100, 2, '.', ''),
                ],
            ]],
        ]);
    }

    /** @return array<string, mixed> */
    public function captureOrder(string $orderId): array
    {
        return $this->request('post', "/v2/checkout/orders/{$orderId}/capture");
    }

    /** @return array<string, mixed> */
    public function getOrder(string $orderId): array
    {
        return $this->request('get', "/v2/checkout/orders/{$orderId}");
    }

    /**
     * @param  array<string, mixed>  $verificationPayload  webhook headers + event body,
     *                                                     shaped per PayPal's verify-webhook-signature contract
     * @return array<string, mixed>
     */
    public function verifyWebhookSignature(array $verificationPayload): array
    {
        return $this->request('post', '/v1/notifications/verify-webhook-signature', $verificationPayload);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        // PHP encodes an empty array as JSON `[]`, but PayPal's schema requires
        // a JSON object (`{}`) for an empty POST body (e.g. the capture call,
        // which takes no fields) — otherwise it rejects it as MALFORMED_REQUEST_JSON.
        // GET requests use $body as query params, where an array is correct.
        $payload = ($method !== 'get' && $body === []) ? (object) [] : $body;

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->{$method}(config('services.paypal.base_url').$path, $payload);

        if ($response->failed()) {
            throw new RuntimeException("PayPal API error [{$method} {$path}] ({$response->status()}): {$response->body()}");
        }

        return $response->json() ?? [];
    }

    private function accessToken(): string
    {
        return Cache::remember('paypal_access_token', now()->addMinutes(25), function () {
            $response = Http::asForm()
                ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.client_secret'))
                ->post(config('services.paypal.base_url').'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new RuntimeException("PayPal OAuth failed ({$response->status()}): {$response->body()}");
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('PayPal OAuth response did not contain an access_token.');
            }

            return $token;
        });
    }
}

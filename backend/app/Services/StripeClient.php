<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the Stripe REST API (PaymentIntents + webhook signature
 * verification). No SDK dependency — just Laravel's HTTP client, mirroring
 * how PayPalClient worked.
 *
 * Unlike PayPal, Stripe webhook signatures are verified locally (HMAC-SHA256
 * over "{timestamp}.{payload}" using the webhook secret) instead of a
 * round-trip API call — see verifyWebhookSignature().
 *
 * Unlike AddressValidationService this does NOT fail open: a broken Stripe
 * call must surface as an error to the caller, never be swallowed, since it
 * guards real money moving.
 */
class StripeClient
{
    /**
     * @param  array<string, string>  $metadata
     * @return array<string, mixed>
     */
    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): array
    {
        return $this->request('post', '/v1/payment_intents', [
            'amount' => $amountCents,
            'currency' => strtolower($currency),
            // Stripe's form-encoded API expects the literal string "true",
            // not PHP's form-encoding of the boolean `true` (which is "1").
            'automatic_payment_methods' => ['enabled' => 'true'],
            'metadata' => $metadata,
        ]);
    }

    /** @return array<string, mixed> */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        return $this->request('get', "/v1/payment_intents/{$paymentIntentId}");
    }

    /**
     * Verifies a Stripe webhook signature per Stripe's documented algorithm:
     * https://docs.stripe.com/webhooks#verify-manually
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || $signatures === []) {
            return false;
        }

        $webhookSecret = config()->string('services.stripe.webhook_secret');
        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $response = Http::withToken(config()->string('services.stripe.secret'))
            ->asForm()
            ->acceptJson()
            ->{$method}('https://api.stripe.com'.$path, $body);

        if ($response->failed()) {
            throw new RuntimeException("Stripe API error [{$method} {$path}] ({$response->status()}): {$response->body()}");
        }

        return $response->json() ?? [];
    }
}

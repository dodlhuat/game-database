<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the Mollie Payments API — no SDK, just Laravel's HTTP
 * client. Test or live mode is decided by the key alone (test_… / live_…).
 *
 * Mollie webhooks carry only a payment id and no signature: the payment is
 * always fetched back from the API with our key, so a forged call can at
 * most make us look up a payment — it can never set its status.
 *
 * Does NOT fail open: a broken call surfaces as an exception, since it
 * guards real money moving.
 */
class MollieClient
{
    private const BASE = 'https://api.mollie.com/v2';

    /**
     * @param  array<string, string>  $metadata
     * @return array<string, mixed>
     */
    public function createPayment(
        int $amountCents,
        string $currency,
        string $description,
        string $redirectUrl,
        array $metadata = [],
    ): array {
        $body = [
            'amount' => [
                'currency' => strtoupper($currency),
                // Mollie wants a string with exactly two decimals
                'value' => number_format($amountCents / 100, 2, '.', ''),
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'locale' => 'de_AT',
            'metadata' => $metadata,
        ];

        // Without a public URL (local dev) the return page confirms on its own
        $webhookUrl = config('services.mollie.webhook_url');
        if (is_string($webhookUrl) && $webhookUrl !== '') {
            $body['webhookUrl'] = $webhookUrl;
        }

        return $this->request('post', '/payments', $body);
    }

    /** @return array<string, mixed> */
    public function getPayment(string $paymentId): array
    {
        return $this->request('get', '/payments/'.rawurlencode($paymentId));
    }

    /**
     * Cancels an open payment. Only allowed where Mollie says isCancelable.
     *
     * @return array<string, mixed>
     */
    public function cancelPayment(string $paymentId): array
    {
        return $this->request('delete', '/payments/'.rawurlencode($paymentId));
    }

    /**
     * Where to send the member to pay.
     *
     * @param  array<string, mixed>  $payment
     */
    public static function checkoutUrl(array $payment): ?string
    {
        $links = $payment['_links'] ?? null;
        $checkout = is_array($links) ? ($links['checkout'] ?? null) : null;
        $href = is_array($checkout) ? ($checkout['href'] ?? null) : null;

        return is_string($href) ? $href : null;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $key = config()->string('services.mollie.key');
        if ($key === '') {
            throw new RuntimeException('Mollie API key is not configured (MOLLIE_KEY).');
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->asJson()
            ->{$method}(self::BASE.$path, $body);

        if ($response->failed()) {
            throw new RuntimeException("Mollie API error [{$method} {$path}] ({$response->status()}): {$response->body()}");
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }
}

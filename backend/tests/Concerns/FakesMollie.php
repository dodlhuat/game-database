<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/** Stands in for the Mollie API: creating a payment and reading it back. */
trait FakesMollie
{
    /**
     * @param  array<string, mixed>  $extra  more fields on the fetched payment, e.g. amountRefunded
     */
    protected function fakeMollie(string $id = 'tr_test1', string $status = 'paid', array $extra = []): void
    {
        config(['services.mollie.key' => 'test_dummy', 'services.mollie.webhook_url' => 'https://api.example.test/api/webhooks/mollie']);

        Http::fake([
            'api.mollie.com/v2/payments' => Http::response([
                'id' => $id,
                'status' => 'open',
                '_links' => ['checkout' => ['href' => "https://www.mollie.com/checkout/{$id}"]],
            ], 201),
            "api.mollie.com/v2/payments/{$id}" => Http::response([
                'id' => $id,
                'status' => $status,
                ...$extra,
            ]),
        ]);
    }

    /** What Mollie sends: a form post with nothing but the payment id. */
    protected function mollieWebhook(string $id): TestResponse
    {
        return $this->post('/api/webhooks/mollie', ['id' => $id]);
    }
}

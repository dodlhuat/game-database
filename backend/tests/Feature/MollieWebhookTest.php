<?php

namespace Tests\Feature;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesMollie;
use Tests\TestCase;

/** Mollie's webhook only names a payment; the status always comes from the API. */
class MollieWebhookTest extends TestCase
{
    use FakesMollie, RefreshDatabase;

    private function purchase(string $id, array $attributes = []): TokenPurchase
    {
        return TokenPurchase::factory()->create([
            'user_id' => User::factory()->member()->create(['tokens' => 5])->id,
            'provider_payment_id' => $id,
            'token_amount' => 20,
            'price_cents' => 1000,
            ...$attributes,
        ]);
    }

    public function test_credits_tokens_when_mollie_reports_paid(): void
    {
        $this->fakeMollie('tr_1', 'paid');
        $purchase = $this->purchase('tr_1');

        $this->mollieWebhook('tr_1')->assertOk();

        $this->assertSame('COMPLETED', $purchase->fresh()->status);
        $this->assertSame(25, $purchase->user->fresh()->tokens);
    }

    public function test_is_idempotent_with_the_return_page(): void
    {
        $this->fakeMollie('tr_1', 'paid');
        $purchase = $this->purchase('tr_1');

        $this->actingAs($purchase->user)->getJson("/api/payments/tokens/{$purchase->id}")->assertOk();
        $this->mollieWebhook('tr_1')->assertOk();
        $this->mollieWebhook('tr_1')->assertOk();

        $this->assertSame(25, $purchase->user->fresh()->tokens);
        $this->assertSame(1, TokenTransaction::where('token_purchase_id', $purchase->id)->count());
    }

    public function test_body_status_is_never_trusted(): void
    {
        $this->fakeMollie('tr_1', 'open');
        $purchase = $this->purchase('tr_1');

        // A forged call claiming success changes nothing: Mollie says "open"
        $this->post('/api/webhooks/mollie', ['id' => 'tr_1', 'status' => 'paid'])->assertOk();

        $this->assertSame('CREATED', $purchase->fresh()->status);
        $this->assertSame(5, $purchase->user->fresh()->tokens);
    }

    public function test_unknown_ids_get_the_same_answer_without_an_api_call(): void
    {
        $this->fakeMollie();

        $this->mollieWebhook('tr_unknown')->assertOk();
        $this->post('/api/webhooks/mollie', [])->assertOk();

        Http::assertNothingSent();
    }

    public function test_refund_and_chargeback_mark_the_purchase_refunded(): void
    {
        $this->fakeMollie('tr_r', 'paid', ['amountRefunded' => ['currency' => 'EUR', 'value' => '10.00']]);
        $refunded = $this->purchase('tr_r', ['status' => 'COMPLETED']);
        $this->mollieWebhook('tr_r')->assertOk();
        $this->assertSame('REFUNDED', $refunded->fresh()->status);

        $this->fakeMollie('tr_c', 'paid', ['amountChargedBack' => ['currency' => 'EUR', 'value' => '10.00']]);
        $chargedBack = $this->purchase('tr_c', ['status' => 'COMPLETED']);
        $this->mollieWebhook('tr_c')->assertOk();
        $this->assertSame('REFUNDED', $chargedBack->fresh()->status);
    }

    public function test_old_stripe_payments_are_left_alone(): void
    {
        $this->fakeMollie('pi_old', 'paid');
        $purchase = $this->purchase('pi_old', ['provider' => 'stripe']);

        $this->mollieWebhook('pi_old')->assertOk();

        $this->assertSame('CREATED', $purchase->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_mollie_errors_make_the_webhook_fail_so_mollie_retries(): void
    {
        config(['services.mollie.key' => 'test_dummy']);
        Http::fake(['api.mollie.com/*' => Http::response([], 503)]);
        $this->purchase('tr_1');

        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);
        $this->mollieWebhook('tr_1');
    }
}

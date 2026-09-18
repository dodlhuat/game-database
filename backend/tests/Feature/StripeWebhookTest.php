<?php

namespace Tests\Feature;

use App\Models\TokenPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Signs a payload exactly like Stripe does, using the same webhook
     * secret configured in phpunit.xml (STRIPE_WEBHOOK_SECRET), so
     * StripeClient::verifyWebhookSignature() accepts it.
     */
    private function signedHeaders(array $event, ?int $timestamp = null): array
    {
        $timestamp ??= time();
        $payload = json_encode($event);
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", config('services.stripe.webhook_secret'));

        return ['Stripe-Signature' => "t={$timestamp},v1={$signature}"];
    }

    private function postEvent(array $event, array $headers): TestResponse
    {
        // Send the raw JSON body ourselves (rather than postJson's array
        // encoding) so it byte-for-byte matches what was signed above.
        return $this->call('POST', '/api/webhooks/stripe', [], [], [], array_merge(
            $this->transformHeadersToServerVars($headers),
            ['CONTENT_TYPE' => 'application/json']
        ), json_encode($event));
    }

    public function test_credits_tokens_for_succeeded_payment_intent(): void
    {
        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $event = [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_1', 'latest_charge' => 'ch_1']],
        ];

        $this->postEvent($event, $this->signedHeaders($event))->assertOk();

        $this->assertDatabaseHas('token_purchases', [
            'id' => $purchase->id,
            'status' => 'COMPLETED',
            'provider_charge_id' => 'ch_1',
        ]);
        $this->assertEquals(25, $user->fresh()->tokens);
    }

    public function test_is_idempotent_with_prior_client_side_confirm(): void
    {
        $user = User::factory()->member()->create(['tokens' => 25]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'COMPLETED',
            'provider_charge_id' => 'ch_1',
        ]);

        $event = [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_1', 'latest_charge' => 'ch_1']],
        ];

        $this->postEvent($event, $this->signedHeaders($event))->assertOk();

        $this->assertDatabaseCount('token_transactions', 0);
        $this->assertEquals(25, $user->fresh()->tokens);
    }

    public function test_rejects_invalid_signature(): void
    {
        $user = User::factory()->member()->create(['tokens' => 5]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $event = [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_1', 'latest_charge' => 'ch_1']],
        ];

        $this->postEvent($event, ['Stripe-Signature' => 't=123,v1=not-a-real-signature'])
            ->assertStatus(400);

        $this->assertEquals(5, $user->fresh()->tokens);
    }

    public function test_ignores_succeeded_event_for_unknown_payment_intent(): void
    {
        $event = [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_does-not-exist', 'latest_charge' => 'ch_x']],
        ];

        $this->postEvent($event, $this->signedHeaders($event))->assertOk();

        $this->assertDatabaseCount('token_purchases', 0);
    }

    public function test_marks_purchase_refunded(): void
    {
        $user = User::factory()->member()->create(['tokens' => 25]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'COMPLETED',
            'provider_charge_id' => 'ch_1',
        ]);

        $event = [
            'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_1']],
        ];

        $this->postEvent($event, $this->signedHeaders($event))->assertOk();

        $this->assertDatabaseHas('token_purchases', [
            'provider_charge_id' => 'ch_1',
            'status' => 'REFUNDED',
        ]);
    }

    public function test_ignores_unknown_event_types(): void
    {
        $event = ['type' => 'customer.created', 'data' => ['object' => ['id' => 'whatever']]];

        $this->postEvent($event, $this->signedHeaders($event))->assertOk();
    }

    public function test_ignores_succeeded_event_without_latest_charge(): void
    {
        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $event = [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_1']],
        ];

        $this->postEvent($event, $this->signedHeaders($event))->assertOk();

        $this->assertDatabaseHas('token_purchases', ['id' => $purchase->id, 'status' => 'CREATED']);
        $this->assertEquals(5, $user->fresh()->tokens);
    }
}

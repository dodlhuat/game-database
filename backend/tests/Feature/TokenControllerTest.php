<?php

namespace Tests\Feature;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_packages_returns_price_list(): void
    {
        $this->getJson('/api/tokens/packages')
            ->assertOk()
            ->assertJson([
                'data' => [
                    ['amount' => 20, 'price_cents' => 1000, 'currency' => 'EUR'],
                    ['amount' => 30, 'price_cents' => 1450, 'currency' => 'EUR'],
                    ['amount' => 40, 'price_cents' => 1850, 'currency' => 'EUR'],
                ],
            ]);
    }

    public function test_checkout_creates_stripe_payment_intent(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_123',
                'client_secret' => 'pi_123_secret_abc',
                'status' => 'requires_payment_method',
            ]),
        ]);

        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertOk()
            ->assertJsonPath('clientSecret', 'pi_123_secret_abc');

        $this->assertDatabaseHas('token_purchases', [
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_123',
            'token_amount' => 20,
            'price_cents' => 1000,
            'currency' => 'EUR',
            'status' => 'CREATED',
        ]);
    }

    public function test_checkout_creates_stripe_payment_intent_for_admin(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_456',
                'client_secret' => 'pi_456_secret_abc',
                'status' => 'requires_payment_method',
            ]),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/tokens/checkout', ['amount' => 40])
            ->assertOk()
            ->assertJsonPath('clientSecret', 'pi_456_secret_abc');
    }

    public function test_checkout_fails_for_non_member(): void
    {
        $user = User::factory()->create(['role' => 'USER', 'status' => 'ACTIVE']);

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertStatus(403);
    }

    public function test_checkout_rejects_invalid_amount(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 15])
            ->assertStatus(422);
    }

    public function test_checkout_returns_bad_gateway_when_stripe_fails(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response(['error' => 'server_error'], 500),
        ]);

        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertStatus(502);

        $this->assertDatabaseCount('token_purchases', 0);
    }

    public function test_confirm_requires_auth(): void
    {
        $this->postJson('/api/tokens/confirm/pi_1')->assertUnauthorized();
    }

    public function test_confirm_credits_tokens_and_creates_transaction(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents/pi_1' => Http::response([
                'id' => 'pi_1',
                'status' => 'succeeded',
                'latest_charge' => 'ch_1',
            ]),
        ]);

        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'price_cents' => 1000,
            'status' => 'CREATED',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tokens/confirm/pi_1')
            ->assertOk()
            ->assertJsonPath('user.tokens', 25);

        $this->assertDatabaseHas('token_purchases', [
            'id' => $purchase->id,
            'status' => 'COMPLETED',
            'provider_charge_id' => 'ch_1',
        ]);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'token_purchase_id' => $purchase->id,
            'type' => 'PURCHASE',
            'amount' => 20,
        ]);
    }

    public function test_confirm_is_idempotent_when_already_completed(): void
    {
        $user = User::factory()->member()->create(['tokens' => 25]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'COMPLETED',
            'provider_charge_id' => 'ch_1',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tokens/confirm/pi_1')
            ->assertOk()
            ->assertJsonPath('user.tokens', 25);

        $this->assertDatabaseCount('token_transactions', 0);
        $this->assertEquals(25, $user->fresh()->tokens);
    }

    public function test_confirm_returns_404_for_unknown_payment_intent(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/confirm/pi_does-not-exist')
            ->assertStatus(404);
    }

    public function test_confirm_returns_404_for_other_users_payment_intent(): void
    {
        $owner = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        TokenPurchase::factory()->create([
            'user_id' => $owner->id,
            'provider_payment_intent_id' => 'pi_1',
            'status' => 'CREATED',
        ]);

        $this->actingAs($other)
            ->postJson('/api/tokens/confirm/pi_1')
            ->assertStatus(404);
    }

    public function test_confirm_marks_failed_when_stripe_payment_not_succeeded(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents/pi_1' => Http::response([
                'id' => 'pi_1',
                'status' => 'requires_payment_method',
            ]),
        ]);

        $user = User::factory()->member()->create(['tokens' => 5]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => 'pi_1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tokens/confirm/pi_1')
            ->assertStatus(422);

        $this->assertDatabaseHas('token_purchases', [
            'provider_payment_intent_id' => 'pi_1',
            'status' => 'FAILED',
        ]);
        $this->assertEquals(5, $user->fresh()->tokens);
    }

    public function test_token_transactions_returns_paginated_meta(): void
    {
        $user = User::factory()->member()->create();
        TokenTransaction::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/token-transactions')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonCount(3, 'data');
    }
}

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
                    ['amount' => 20, 'price_cents' => 50, 'currency' => 'EUR'],
                    ['amount' => 30, 'price_cents' => 100, 'currency' => 'EUR'],
                    ['amount' => 40, 'price_cents' => 150, 'currency' => 'EUR'],
                ],
            ]);
    }

    public function test_checkout_creates_paypal_order(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 32000]),
            '*/v2/checkout/orders' => Http::response(['id' => 'PAYPAL-ORDER-123', 'status' => 'CREATED']),
        ]);

        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertOk()
            ->assertJsonPath('orderID', 'PAYPAL-ORDER-123');

        $this->assertDatabaseHas('token_purchases', [
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-123',
            'token_amount' => 20,
            'price_cents' => 50,
            'currency' => 'EUR',
            'status' => 'CREATED',
        ]);
    }

    public function test_checkout_creates_paypal_order_for_admin(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token']),
            '*/v2/checkout/orders' => Http::response(['id' => 'PAYPAL-ORDER-456', 'status' => 'CREATED']),
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/tokens/checkout', ['amount' => 40])
            ->assertOk()
            ->assertJsonPath('orderID', 'PAYPAL-ORDER-456');
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

    public function test_checkout_returns_bad_gateway_when_paypal_fails(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token']),
            '*/v2/checkout/orders' => Http::response(['error' => 'server_error'], 500),
        ]);

        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertStatus(502);

        $this->assertDatabaseCount('token_purchases', 0);
    }

    public function test_capture_requires_auth(): void
    {
        $this->postJson('/api/tokens/capture/PAYPAL-ORDER-1')->assertUnauthorized();
    }

    public function test_capture_credits_tokens_and_creates_transaction(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token']),
            '*/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => ['captures' => [['id' => 'CAPTURE-1', 'status' => 'COMPLETED']]],
                ]],
            ]),
        ]);

        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'price_cents' => 50,
            'status' => 'CREATED',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tokens/capture/PAYPAL-ORDER-1')
            ->assertOk()
            ->assertJsonPath('user.tokens', 25);

        $this->assertDatabaseHas('token_purchases', [
            'id' => $purchase->id,
            'status' => 'COMPLETED',
            'paypal_capture_id' => 'CAPTURE-1',
        ]);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'token_purchase_id' => $purchase->id,
            'type' => 'PURCHASE',
            'amount' => 20,
        ]);
    }

    public function test_capture_is_idempotent_when_already_completed(): void
    {
        $user = User::factory()->member()->create(['tokens' => 25]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'status' => 'COMPLETED',
            'paypal_capture_id' => 'CAPTURE-1',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tokens/capture/PAYPAL-ORDER-1')
            ->assertOk()
            ->assertJsonPath('user.tokens', 25);

        $this->assertDatabaseCount('token_transactions', 0);
        $this->assertEquals(25, $user->fresh()->tokens);
    }

    public function test_capture_returns_404_for_unknown_order(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/capture/DOES-NOT-EXIST')
            ->assertStatus(404);
    }

    public function test_capture_returns_404_for_other_users_order(): void
    {
        $owner = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        TokenPurchase::factory()->create([
            'user_id' => $owner->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'status' => 'CREATED',
        ]);

        $this->actingAs($other)
            ->postJson('/api/tokens/capture/PAYPAL-ORDER-1')
            ->assertStatus(404);
    }

    public function test_capture_marks_failed_when_paypal_declines(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token']),
            '*/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
                'status' => 'DECLINED',
            ]),
        ]);

        $user = User::factory()->member()->create(['tokens' => 5]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tokens/capture/PAYPAL-ORDER-1')
            ->assertStatus(422);

        $this->assertDatabaseHas('token_purchases', [
            'paypal_order_id' => 'PAYPAL-ORDER-1',
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

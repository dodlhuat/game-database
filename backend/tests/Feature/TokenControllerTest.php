<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesMollie;
use Tests\TestCase;

class TokenControllerTest extends TestCase
{
    use FakesMollie, RefreshDatabase;

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

    public function test_checkout_opens_a_mollie_payment(): void
    {
        $this->fakeMollie('tr_123');
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertOk()
            ->assertJsonPath('checkoutUrl', 'https://www.mollie.com/checkout/tr_123');

        $this->assertDatabaseHas('token_purchases', [
            'user_id' => $user->id,
            'provider' => 'mollie',
            'provider_payment_id' => 'tr_123',
            'token_amount' => 20,
            'price_cents' => 1000,
            'currency' => 'EUR',
            'status' => 'CREATED',
        ]);
        Http::assertSent(fn ($r) => $r->method() === 'POST'
            && $r['amount'] === ['currency' => 'EUR', 'value' => '10.00']
            && $r['webhookUrl'] === 'https://api.example.test/api/webhooks/mollie');
    }

    public function test_checkout_works_for_admins(): void
    {
        $this->fakeMollie('tr_456');

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/tokens/checkout', ['amount' => 40])
            ->assertOk()
            ->assertJsonPath('checkoutUrl', 'https://www.mollie.com/checkout/tr_456');
    }

    public function test_checkout_omits_the_webhook_without_a_public_url(): void
    {
        $this->fakeMollie();
        config(['services.mollie.webhook_url' => '']);

        $this->actingAs(User::factory()->member()->create())
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertOk();

        Http::assertSent(fn ($r) => $r->method() === 'POST' && ! isset($r['webhookUrl']));
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

    public function test_checkout_returns_bad_gateway_when_mollie_fails(): void
    {
        config(['services.mollie.key' => 'test_dummy']);
        Http::fake(['api.mollie.com/*' => Http::response(['detail' => 'server error'], 500)]);
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/checkout', ['amount' => 20])
            ->assertStatus(502);

        $this->assertDatabaseHas('token_purchases', ['user_id' => $user->id, 'status' => 'FAILED']);
    }

    public function test_return_page_requires_auth(): void
    {
        $this->getJson('/api/payments/tokens/1')->assertUnauthorized();
    }

    public function test_return_page_credits_tokens_once_mollie_says_paid(): void
    {
        $this->fakeMollie('tr_1', 'paid');
        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'provider_payment_id' => 'tr_1',
            'token_amount' => 20,
            'price_cents' => 1000,
            'status' => 'CREATED',
        ]);

        $this->actingAs($user)
            ->getJson("/api/payments/tokens/{$purchase->id}")
            ->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('user.tokens', 25);
        $this->actingAs($user)->getJson("/api/payments/tokens/{$purchase->id}")->assertOk();

        $this->assertDatabaseHas('token_purchases', ['id' => $purchase->id, 'status' => 'COMPLETED', 'provider_charge_id' => 'tr_1']);
        $this->assertSame(1, TokenTransaction::where('token_purchase_id', $purchase->id)->where('type', 'PURCHASE')->count());
        $this->assertSame(25, $user->fresh()->tokens);
    }

    public function test_return_page_reports_open_payments_as_pending(): void
    {
        $this->fakeMollie('tr_1', 'open');
        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create(['user_id' => $user->id, 'provider_payment_id' => 'tr_1']);

        $this->actingAs($user)
            ->getJson("/api/payments/tokens/{$purchase->id}")
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->assertSame('CREATED', $purchase->fresh()->status);
    }

    public function test_return_page_marks_cancelled_and_expired_payments_failed(): void
    {
        foreach (['canceled', 'expired', 'failed'] as $i => $status) {
            $this->fakeMollie("tr_{$i}", $status);
            $user = User::factory()->member()->create(['tokens' => 5]);
            $purchase = TokenPurchase::factory()->create(['user_id' => $user->id, 'provider_payment_id' => "tr_{$i}"]);

            $this->actingAs($user)
                ->getJson("/api/payments/tokens/{$purchase->id}")
                ->assertOk()
                ->assertJsonPath('status', 'failed');

            $this->assertSame('FAILED', $purchase->fresh()->status);
            $this->assertSame(5, $user->fresh()->tokens);
        }
    }

    public function test_return_page_returns_404_for_other_users_and_unknown_ids(): void
    {
        $this->fakeMollie('tr_1');
        $owner = User::factory()->member()->create();
        $purchase = TokenPurchase::factory()->create(['user_id' => $owner->id, 'provider_payment_id' => 'tr_1']);

        $this->actingAs(User::factory()->member()->create())
            ->getJson("/api/payments/tokens/{$purchase->id}")->assertNotFound();
        $this->actingAs($owner)->getJson('/api/payments/tokens/99999')->assertNotFound();
        $this->actingAs($owner)->getJson('/api/payments/stripe/1')->assertNotFound();
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

    public function test_token_history_names_the_borrowed_game(): void
    {
        $user = User::factory()->member()->create();
        $loan = Loan::factory()->create(['user_id' => $user->id]);
        TokenTransaction::factory()->create([
            'user_id' => $user->id,
            'loan_id' => $loan->id,
            'type' => 'BORROW',
            'amount' => -3,
        ]);

        $this->actingAs($user)->getJson('/api/token-transactions')
            ->assertOk()
            ->assertJsonPath('data.0.loan.copy.game.title', $loan->copy?->game?->title)
            ->assertJsonPath('data.0.loan.copy.game.slug', $loan->copy?->game?->slug);
    }
}

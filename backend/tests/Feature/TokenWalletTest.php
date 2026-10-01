<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TokenWalletTest extends TestCase
{
    use RefreshDatabase;

    private TokenWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = app(TokenWallet::class);
    }

    public function test_grant_tokens_creates_lot_and_increases_balance(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);

        $lot = $this->wallet->grantTokens($user, 20, 'PURCHASE', 50, now()->addDays(30));

        $this->assertSame(20, $user->tokens);
        $this->assertSame(20, $lot->remaining);
        $this->assertSame(50, $lot->unit_cents);
        $this->assertSame('NORMAL', $lot->kind);
    }

    public function test_grant_bonus_creates_expiring_lot_and_transaction(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);

        $lot = $this->wallet->grantBonus($user, 20, 'MEMBERSHIP', now()->addMonths(12));

        $this->assertSame(20, $user->bonus_tokens);
        $this->assertSame(0, $user->tokens);
        $this->assertTrue($lot->expires_at->isFuture());
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => 'BONUS_GRANT',
            'amount' => 20,
            'token_lot_id' => $lot->id,
        ]);
    }

    public function test_spend_uses_bonus_before_normal_tokens(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);
        $this->wallet->grantTokens($user, 10, 'PURCHASE', 50);
        $this->wallet->grantBonus($user, 5, 'MEMBERSHIP', now()->addMonths(12));

        $this->wallet->spend($user, 7);

        $this->assertSame(0, $user->bonus_tokens);
        $this->assertSame(8, $user->tokens);
    }

    public function test_spend_uses_earliest_expiring_bonus_first(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);
        $late = $this->wallet->grantBonus($user, 5, 'MEMBERSHIP', now()->addMonths(12));
        $early = $this->wallet->grantBonus($user, 5, 'MEMBERSHIP', now()->addMonths(2));

        $this->wallet->spend($user, 3);

        $this->assertSame(2, $early->fresh()->remaining);
        $this->assertSame(5, $late->fresh()->remaining);
        $this->assertSame(7, $user->bonus_tokens);
    }

    public function test_spend_uses_legacy_tokens_then_oldest_lot(): void
    {
        // 4 tokens exist without a lot (legacy, worthless), plus a paid lot
        $user = User::factory()->member()->create(['tokens' => 4]);
        $lot = $this->wallet->grantTokens($user, 10, 'PURCHASE', 50);

        $this->wallet->spend($user, 6);

        $this->assertSame(8, $lot->fresh()->remaining);
        $this->assertSame(8, $user->tokens);
    }

    public function test_spend_throws_when_balance_is_insufficient(): void
    {
        $user = User::factory()->member()->create(['tokens' => 2]);

        $this->expectException(RuntimeException::class);
        $this->wallet->spend($user, 3);
    }

    public function test_expire_bonus_removes_expired_lots_only(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);
        $expired = $this->wallet->grantBonus($user, 5, 'MEMBERSHIP', now()->subDay());
        $valid = $this->wallet->grantBonus($user, 7, 'MEMBERSHIP', now()->addMonth());
        $this->wallet->spend($user, 2); // consumes from the expiring lot first

        $count = $this->wallet->expireBonus();

        $this->assertSame(1, $count);
        $this->assertNotNull($expired->fresh()->expired_at);
        $this->assertSame(0, $expired->fresh()->remaining);
        $this->assertSame(7, $valid->fresh()->remaining);
        $this->assertSame(7, $user->fresh()->bonus_tokens);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => 'BONUS_EXPIRE',
            'amount' => -3,
        ]);

        // idempotent
        $this->assertSame(0, $this->wallet->expireBonus());
    }

    public function test_expire_command_runs(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);
        $this->wallet->grantBonus($user, 5, 'MEMBERSHIP', now()->subMinute());

        $this->artisan('tokens:expire-bonus')->assertSuccessful();

        $this->assertSame(0, $user->fresh()->bonus_tokens);
    }

    public function test_free_tokens_include_bonus(): void
    {
        $user = User::factory()->member()->create(['tokens' => 3, 'tokens_blocked' => 2]);
        $this->wallet->grantBonus($user, 5, 'MEMBERSHIP', now()->addMonth());

        $this->assertSame(6, $user->freeTokens());
        $this->assertTrue($user->hasEnoughFreeTokens(6));
        $this->assertFalse($user->hasEnoughFreeTokens(7));
    }
}

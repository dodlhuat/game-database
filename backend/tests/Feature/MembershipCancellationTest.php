<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\MembershipCancellation;
use App\Models\User;
use App\Notifications\MembershipCancellationConfirmation;
use App\Notifications\MembershipCancellationRequested;
use App\Services\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipCancellationTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'AT61 1904 3002 3457 3201';

    private TokenWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = app(TokenWallet::class);
    }

    /** Member with 20 membership tokens (50 ct), 20 bonus tokens and a long-ago purchase of 40 tokens (46 ct). */
    private function member(): User
    {
        $user = User::factory()->member()->create(['tokens' => 0]);
        $this->wallet->grantTokens($user, 20, 'MEMBERSHIP', 50);
        $this->wallet->grantBonus($user, 20, 'MEMBERSHIP', now()->addYear());
        $this->wallet->grantTokens($user, 40, 'PURCHASE', 46, now()->subDay());

        return $user->fresh();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/membership/cancel/preview')->assertUnauthorized();
        $this->postJson('/api/membership/cancel')->assertUnauthorized();
    }

    public function test_non_members_cannot_cancel(): void
    {
        $user = User::factory()->create(['role' => 'USER']);

        $this->actingAs($user)->getJson('/api/membership/cancel/preview')->assertUnprocessable();
        $this->actingAs($user)->postJson('/api/membership/cancel', ['confirm' => true])->assertUnprocessable();
    }

    public function test_preview_values_tokens_at_paid_price_minus_fee(): void
    {
        $user = $this->member();

        // 20 * 50 + 40 * 46 = 2840 gross, minus 200 fee
        $this->actingAs($user)->getJson('/api/membership/cancel/preview')
            ->assertOk()
            ->assertJson([
                'tokens' => 60,
                'bonus_tokens' => 20,
                'refund_tokens' => 60,
                'gross_cents' => 2840,
                'fee_cents' => 200,
                'refund_cents' => 2640,
                'non_refundable_tokens' => 0,
                'blockers' => [],
            ]);
    }

    public function test_tokens_inside_the_waiting_period_and_legacy_tokens_are_not_refunded(): void
    {
        $user = User::factory()->member()->create(['tokens' => 5]); // 5 legacy tokens without lot
        $this->wallet->grantTokens($user, 10, 'PURCHASE', 50, now()->addDays(10)); // still waiting
        $this->wallet->grantTokens($user, 4, 'MEMBERSHIP', 50);

        $this->actingAs($user->fresh())->getJson('/api/membership/cancel/preview')
            ->assertOk()
            ->assertJson([
                'tokens' => 19,
                'refund_tokens' => 4,
                'gross_cents' => 200,
                'fee_cents' => 200,
                'refund_cents' => 0,
                'non_refundable_tokens' => 15,
            ]);
    }

    public function test_fee_never_exceeds_refundable_amount(): void
    {
        $user = User::factory()->member()->create(['tokens' => 0]);
        $this->wallet->grantTokens($user, 2, 'MEMBERSHIP', 50);

        $this->actingAs($user->fresh())->getJson('/api/membership/cancel/preview')
            ->assertJson(['gross_cents' => 100, 'fee_cents' => 100, 'refund_cents' => 0]);
    }

    public function test_open_loans_block_the_cancellation(): void
    {
        $user = $this->member();
        Loan::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->getJson('/api/membership/cancel/preview')
            ->assertJsonPath('blockers', ['open_loans']);

        $this->actingAs($user)->postJson('/api/membership/cancel', [
            'confirm' => true, 'account_holder' => 'Max', 'iban' => self::IBAN,
        ])->assertUnprocessable()->assertJsonPath('blockers', ['open_loans']);

        $this->assertSame('MEMBER', $user->fresh()->role);
    }

    public function test_blocked_deposit_tokens_block_the_cancellation(): void
    {
        $user = $this->member();
        $user->update(['tokens_blocked' => 3]);

        $this->actingAs($user)->postJson('/api/membership/cancel', [
            'confirm' => true, 'account_holder' => 'Max', 'iban' => self::IBAN,
        ])->assertUnprocessable()->assertJsonPath('blockers', ['blocked_tokens']);
    }

    public function test_cancel_requires_confirmation_and_bank_details_when_there_is_a_refund(): void
    {
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/membership/cancel', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['confirm', 'account_holder', 'iban']);

        $this->actingAs($user)->postJson('/api/membership/cancel', [
            'confirm' => true, 'account_holder' => 'Max', 'iban' => 'AT00 0000 0000 0000 0000',
        ])->assertUnprocessable()->assertJsonValidationErrors(['iban']);

        $this->assertSame('MEMBER', $user->fresh()->role);
    }

    public function test_cancel_closes_membership_and_empties_the_wallet(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = $this->member();

        $this->actingAs($user)->postJson('/api/membership/cancel', [
            'confirm' => true,
            'account_holder' => 'Max Muster',
            'iban' => self::IBAN,
            'reason' => 'Zieht um',
        ])
            ->assertOk()
            ->assertJsonPath('refund_cents', 2640)
            ->assertJsonPath('refund_tokens', 60)
            ->assertJsonPath('user.role', 'USER')
            ->assertJsonPath('user.tokens', 0)
            ->assertJsonPath('user.bonus_tokens', 0);

        $user->refresh();
        $this->assertNull($user->membership_expires_at);
        $this->assertDatabaseCount('token_lots', 3);
        $this->assertSame(0, (int) DB::table('token_lots')->where('user_id', $user->id)->sum('remaining'));
        $this->assertDatabaseHas('token_transactions', ['user_id' => $user->id, 'type' => 'REFUND', 'amount' => -60]);
        $this->assertDatabaseHas('token_transactions', ['user_id' => $user->id, 'type' => 'BONUS_EXPIRE', 'amount' => -20]);

        $c = MembershipCancellation::firstOrFail();
        $this->assertSame('REQUESTED', $c->status);
        $this->assertSame('AT611904300234573201', $c->iban);
        $this->assertSame('Zieht um', $c->reason);
        $this->assertSame(20, $c->bonus_tokens_forfeited);
        // IBAN is stored encrypted
        $this->assertStringNotContainsString('AT61', DB::table('membership_cancellations')->value('iban'));

        Notification::assertSentTo($admin, MembershipCancellationRequested::class);
        Notification::assertSentTo($user, MembershipCancellationConfirmation::class);
    }

    public function test_cancel_without_refund_needs_no_bank_details(): void
    {
        Notification::fake();
        $user = User::factory()->member()->create(['tokens' => 7]); // legacy tokens only

        $this->actingAs($user)->postJson('/api/membership/cancel', ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('refund_cents', 0)
            ->assertJsonPath('user.role', 'USER');

        $c = MembershipCancellation::firstOrFail();
        $this->assertSame('NO_REFUND', $c->status);
        $this->assertNull($c->iban);
    }

    public function test_supporter_can_cancel(): void
    {
        Notification::fake();
        $user = User::factory()->supporter()->create();

        $this->actingAs($user)->postJson('/api/membership/cancel', ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('user.role', 'USER');
    }

    public function test_cancelled_user_cannot_cancel_twice(): void
    {
        Notification::fake();
        $user = User::factory()->supporter()->create();
        $this->actingAs($user)->postJson('/api/membership/cancel', ['confirm' => true])->assertOk();

        $this->actingAs($user)->postJson('/api/membership/cancel', ['confirm' => true])->assertUnprocessable();
        $this->assertDatabaseCount('membership_cancellations', 1);
    }

    public function test_admin_can_list_and_mark_cancellations_as_refunded(): void
    {
        Notification::fake();
        $user = $this->member();
        $this->actingAs($user)->postJson('/api/membership/cancel', [
            'confirm' => true, 'account_holder' => 'Max', 'iban' => self::IBAN,
        ])->assertOk();
        $c = MembershipCancellation::firstOrFail();

        $this->actingAs($user->fresh())->getJson('/api/admin/cancellations')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->getJson('/api/admin/cancellations')
            ->assertOk()
            ->assertJsonPath('data.0.iban', 'AT611904300234573201')
            ->assertJsonPath('data.0.refund_cents', 2640)
            ->assertJsonPath('data.0.user.id', $user->id);

        $this->actingAs($admin)->patchJson("/api/admin/cancellations/{$c->id}/refunded")->assertOk();
        $this->assertSame('REFUNDED', $c->fresh()->status);
        $this->assertNotNull($c->fresh()->refunded_at);

        $this->actingAs($admin)->patchJson("/api/admin/cancellations/{$c->id}/refunded")->assertUnprocessable();
    }
}

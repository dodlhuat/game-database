<?php

namespace Tests\Feature;

use App\Models\MembershipPayment;
use App\Models\TokenLot;
use App\Models\User;
use App\Notifications\WelcomeMemberNotification;
use App\Notifications\WelcomeSupporterNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\FakesMollie;
use Tests\TestCase;

class MembershipPaymentTest extends TestCase
{
    use FakesMollie, RefreshDatabase;

    private const ADDRESS = ['street' => 'Hauptstraße 1', 'postal_code' => '1010', 'city' => 'Wien'];

    /** The member coming back from the Mollie checkout to the return page. */
    private function returnFromMollie(User $user): TestResponse
    {
        $payment = MembershipPayment::where('user_id', $user->id)->latest('id')->firstOrFail();

        return $this->actingAs($user)->getJson("/api/payments/membership/{$payment->id}");
    }

    public function test_pricing_comes_from_config(): void
    {
        $this->getJson('/api/membership/pricing')
            ->assertOk()
            ->assertJson([
                'fee_cents' => 2400,
                'currency' => 'EUR',
                'bonus_tokens' => 20,
                'bonus_valid_months' => 12,
            ]);
    }

    public function test_old_free_upgrade_endpoints_are_gone(): void
    {
        $user = User::factory()->create(['role' => 'USER']);

        $this->actingAs($user)->postJson('/api/membership/upgrade', self::ADDRESS)->assertNotFound();
        $this->actingAs($user)->postJson('/api/membership/upgrade-supporter', self::ADDRESS)->assertNotFound();
        $this->actingAs($user)->postJson('/api/membership/renew')->assertNotFound();
        $this->assertSame('USER', $user->fresh()->role);
    }

    public function test_checkout_creates_intent_with_config_price_and_does_not_activate(): void
    {
        $this->fakeMollie();
        $user = User::factory()->create(['role' => 'USER']);

        $this->actingAs($user)
            ->postJson('/api/membership/checkout', ['type' => 'MEMBER', 'price_cents' => 1] + self::ADDRESS)
            ->assertOk()
            ->assertJsonPath('checkoutUrl', 'https://www.mollie.com/checkout/tr_test1');

        $this->assertDatabaseHas('membership_payments', [
            'user_id' => $user->id,
            'type' => 'MEMBER',
            'price_cents' => 2400,
            'status' => 'CREATED',
            'city' => 'Wien',
        ]);
        $this->assertSame('USER', $user->fresh()->role);
        $this->assertDatabaseHas('membership_payments', ['provider' => 'mollie', 'provider_payment_id' => 'tr_test1']);
        Http::assertSent(fn ($r) => $r->method() === 'POST'
            && $r['amount'] === ['currency' => 'EUR', 'value' => '24.00']
            && $r['metadata']['kind'] === 'membership'
            && str_contains($r['redirectUrl'], '/zahlung?art=membership&id='));
    }

    public function test_checkout_requires_address_for_new_membership(): void
    {
        $this->fakeMollie();
        $user = User::factory()->create(['role' => 'USER']);

        $this->actingAs($user)
            ->postJson('/api/membership/checkout', ['type' => 'MEMBER'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['street', 'postal_code', 'city']);
    }

    public function test_checkout_rejects_existing_members_for_new_membership(): void
    {
        $this->fakeMollie();

        $this->actingAs(User::factory()->member()->create())
            ->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)
            ->assertUnprocessable();
    }

    public function test_confirm_activates_member_with_tokens_and_bonus(): void
    {
        Notification::fake();
        $this->fakeMollie();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $this->returnFromMollie($user)
            ->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('user.role', 'MEMBER')
            ->assertJsonPath('user.tokens', 0)
            ->assertJsonPath('user.bonus_tokens', 20)
            ->assertJsonPath('user.bonus_lots.0.remaining', 20)
            ->assertJsonCount(1, 'user.bonus_lots');

        $user->refresh();
        $this->assertTrue($user->membership_expires_at->isFuture());
        $this->assertSame('Wien', $user->city);
        $this->assertDatabaseHas('membership_payments', ['status' => 'COMPLETED', 'provider_charge_id' => 'tr_test1']);

        $this->assertFalse(TokenLot::where('user_id', $user->id)->where('kind', 'NORMAL')->exists());
        $bonus = TokenLot::where('user_id', $user->id)->where('kind', 'BONUS')->firstOrFail();
        $this->assertEqualsWithDelta(now()->addMonths(12)->timestamp, $bonus->expires_at->timestamp, 5);
        Notification::assertSentTo($user, WelcomeMemberNotification::class);
    }

    public function test_confirm_activates_supporter_without_tokens(): void
    {
        Notification::fake();
        $this->fakeMollie();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'SUPPORTER'] + self::ADDRESS)->assertOk();

        $this->returnFromMollie($user)
            ->assertOk()
            ->assertJsonPath('user.role', 'SUPPORTER');

        $this->assertSame(0, $user->fresh()->tokens + $user->fresh()->bonus_tokens);
        Notification::assertSentTo($user, WelcomeSupporterNotification::class);
    }

    public function test_cancelled_payment_does_not_activate(): void
    {
        $this->fakeMollie(status: 'canceled');
        $user = User::factory()->create(['role' => 'USER']);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $this->returnFromMollie($user)->assertOk()->assertJsonPath('status', 'failed');

        $this->assertSame('USER', $user->fresh()->role);
        $this->assertDatabaseHas('membership_payments', ['status' => 'FAILED']);
    }

    public function test_confirm_is_idempotent_and_other_users_cannot_confirm(): void
    {
        Notification::fake();
        $this->fakeMollie();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $payment = MembershipPayment::where('user_id', $user->id)->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'USER']))
            ->getJson("/api/payments/membership/{$payment->id}")->assertNotFound();

        $this->returnFromMollie($user)->assertOk();
        $this->returnFromMollie($user)->assertOk();

        $this->assertSame(0, $user->fresh()->tokens);
        $this->assertSame(20, $user->fresh()->bonus_tokens);
    }

    public function test_webhook_and_return_page_activate_the_membership_only_once(): void
    {
        Notification::fake();
        $this->fakeMollie();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $this->mollieWebhook('tr_test1')->assertOk();
        $this->returnFromMollie($user)->assertOk();
        $this->mollieWebhook('tr_test1')->assertOk();

        $user->refresh();
        $this->assertSame('MEMBER', $user->role);
        $this->assertSame(0, $user->tokens);
        $this->assertSame(20, $user->bonus_tokens);
    }

    public function test_renewal_extends_from_expiry_and_grants_package_to_members(): void
    {
        Notification::fake();
        $this->fakeMollie();
        $expires = now()->addMonths(2);
        $user = User::factory()->member()->create(['tokens' => 3, 'membership_expires_at' => $expires]);

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertOk();
        $this->returnFromMollie($user)->assertOk();

        $user->refresh();
        $this->assertEqualsWithDelta($expires->copy()->addYear()->timestamp, $user->membership_expires_at->timestamp, 5);
        $this->assertSame(3, $user->tokens);
        $this->assertSame(20, $user->bonus_tokens);
    }

    public function test_renewal_is_rejected_more_than_three_months_before_expiry(): void
    {
        $this->fakeMollie();
        $user = User::factory()->member()->create(['membership_expires_at' => now()->addMonths(6)]);

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertUnprocessable();
    }

    public function test_expired_membership_can_be_renewed_and_restarts_from_today(): void
    {
        Notification::fake();
        $this->fakeMollie();
        $user = User::factory()->member()->create(['tokens' => 3, 'membership_expires_at' => now()->subMonths(2)]);
        $this->assertFalse($user->isMember());

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertOk();
        $this->returnFromMollie($user)->assertOk();

        $user->refresh();
        $this->assertTrue($user->isMember());
        $this->assertEqualsWithDelta(now()->addYear()->timestamp, $user->membership_expires_at->timestamp, 5);
        $this->assertSame(3, $user->tokens);
        $this->assertSame(20, $user->bonus_tokens);
    }

    public function test_supporter_renewal_grants_no_tokens(): void
    {
        $this->fakeMollie();
        $user = User::factory()->supporter()->create(['membership_expires_at' => now()->addMonth()]);

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertOk();
        $this->returnFromMollie($user)->assertOk();

        $this->assertSame(0, $user->fresh()->tokens);
        $this->assertSame(0, $user->fresh()->bonus_tokens);
    }

    public function test_activate_full_membership_grants_package_to_supporter(): void
    {
        Notification::fake();
        $user = User::factory()->supporter()->create();

        $this->actingAs($user)->postJson('/api/membership/activate-full')
            ->assertOk()
            ->assertJsonPath('user.role', 'MEMBER')
            ->assertJsonPath('user.tokens', 0)
            ->assertJsonPath('user.bonus_tokens', 20);
    }

    public function test_activate_full_membership_is_rejected_for_expired_supporter(): void
    {
        $user = User::factory()->supporter()->create(['membership_expires_at' => now()->subDay()]);

        $this->actingAs($user)->postJson('/api/membership/activate-full')->assertUnprocessable();

        $user->refresh();
        $this->assertSame('SUPPORTER', $user->role);
        $this->assertSame(0, $user->bonus_tokens);
    }

    public function test_refund_at_mollie_marks_membership_payment(): void
    {
        $this->fakeMollie('tr_x', 'paid', ['amountRefunded' => ['currency' => 'EUR', 'value' => '24.00']]);
        $user = User::factory()->create();
        MembershipPayment::create([
            'user_id' => $user->id, 'type' => 'MEMBER', 'provider' => 'mollie', 'provider_payment_id' => 'tr_x',
            'provider_charge_id' => 'tr_x', 'price_cents' => 2400, 'status' => 'COMPLETED',
        ]);

        $this->mollieWebhook('tr_x')->assertOk();

        $this->assertDatabaseHas('membership_payments', ['provider_payment_id' => 'tr_x', 'status' => 'REFUNDED']);
    }
}

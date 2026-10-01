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
use Tests\TestCase;

class MembershipPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = ['street' => 'Hauptstraße 1', 'postal_code' => '1010', 'city' => 'Wien'];

    private function fakeStripe(string $id = 'pi_m1', string $status = 'succeeded'): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => $id,
                'client_secret' => "{$id}_secret",
                'status' => 'requires_payment_method',
            ]),
            "api.stripe.com/v1/payment_intents/{$id}" => Http::response([
                'id' => $id,
                'status' => $status,
                'latest_charge' => 'ch_m1',
            ]),
        ]);
    }

    private function signedWebhook(array $event): TestResponse
    {
        $timestamp = time();
        $payload = json_encode($event);
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", config('services.stripe.webhook_secret'));

        return $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_pricing_comes_from_config(): void
    {
        $this->getJson('/api/membership/pricing')
            ->assertOk()
            ->assertJson([
                'fee_cents' => 2400,
                'currency' => 'EUR',
                'tokens' => 20,
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
        $this->fakeStripe();
        $user = User::factory()->create(['role' => 'USER']);

        $this->actingAs($user)
            ->postJson('/api/membership/checkout', ['type' => 'MEMBER', 'price_cents' => 1] + self::ADDRESS)
            ->assertOk()
            ->assertJsonPath('clientSecret', 'pi_m1_secret')
            ->assertJsonPath('price_cents', 2400);

        $this->assertDatabaseHas('membership_payments', [
            'user_id' => $user->id,
            'type' => 'MEMBER',
            'price_cents' => 2400,
            'status' => 'CREATED',
            'city' => 'Wien',
        ]);
        $this->assertSame('USER', $user->fresh()->role);
        Http::assertSent(fn ($r) => $r['amount'] === 2400 && $r['metadata']['kind'] === 'membership');
    }

    public function test_checkout_requires_address_for_new_membership(): void
    {
        $this->fakeStripe();
        $user = User::factory()->create(['role' => 'USER']);

        $this->actingAs($user)
            ->postJson('/api/membership/checkout', ['type' => 'MEMBER'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['street', 'postal_code', 'city']);
    }

    public function test_checkout_rejects_existing_members_for_new_membership(): void
    {
        $this->fakeStripe();

        $this->actingAs(User::factory()->member()->create())
            ->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)
            ->assertUnprocessable();
    }

    public function test_confirm_activates_member_with_tokens_and_bonus(): void
    {
        Notification::fake();
        $this->fakeStripe();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $this->actingAs($user)
            ->postJson('/api/membership/confirm/pi_m1')
            ->assertOk()
            ->assertJsonPath('user.role', 'MEMBER')
            ->assertJsonPath('user.tokens', 20)
            ->assertJsonPath('user.bonus_tokens', 20)
            ->assertJsonPath('user.bonus_lots.0.remaining', 20)
            ->assertJsonCount(1, 'user.bonus_lots');

        $user->refresh();
        $this->assertTrue($user->membership_expires_at->isFuture());
        $this->assertSame('Wien', $user->city);
        $this->assertDatabaseHas('membership_payments', ['status' => 'COMPLETED', 'provider_charge_id' => 'ch_m1']);

        $normal = TokenLot::where('user_id', $user->id)->where('kind', 'NORMAL')->firstOrFail();
        $this->assertSame(50, $normal->unit_cents);
        $this->assertNull($normal->refundable_after);
        $bonus = TokenLot::where('user_id', $user->id)->where('kind', 'BONUS')->firstOrFail();
        $this->assertEqualsWithDelta(now()->addMonths(12)->timestamp, $bonus->expires_at->timestamp, 5);
        Notification::assertSentTo($user, WelcomeMemberNotification::class);
    }

    public function test_confirm_activates_supporter_without_tokens(): void
    {
        Notification::fake();
        $this->fakeStripe();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'SUPPORTER'] + self::ADDRESS)->assertOk();

        $this->actingAs($user)->postJson('/api/membership/confirm/pi_m1')
            ->assertOk()
            ->assertJsonPath('user.role', 'SUPPORTER');

        $this->assertSame(0, $user->fresh()->tokens + $user->fresh()->bonus_tokens);
        Notification::assertSentTo($user, WelcomeSupporterNotification::class);
    }

    public function test_confirm_does_not_activate_unpaid_intent(): void
    {
        $this->fakeStripe(status: 'requires_payment_method');
        $user = User::factory()->create(['role' => 'USER']);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $this->actingAs($user)->postJson('/api/membership/confirm/pi_m1')->assertUnprocessable();

        $this->assertSame('USER', $user->fresh()->role);
        $this->assertDatabaseHas('membership_payments', ['status' => 'FAILED']);
    }

    public function test_confirm_is_idempotent_and_other_users_cannot_confirm(): void
    {
        Notification::fake();
        $this->fakeStripe();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'USER']))
            ->postJson('/api/membership/confirm/pi_m1')->assertNotFound();

        $this->actingAs($user)->postJson('/api/membership/confirm/pi_m1')->assertOk();
        $this->actingAs($user)->postJson('/api/membership/confirm/pi_m1')->assertOk();

        $this->assertSame(20, $user->fresh()->tokens);
        $this->assertSame(20, $user->fresh()->bonus_tokens);
    }

    public function test_webhook_activates_membership_once_even_after_confirm(): void
    {
        Notification::fake();
        $this->fakeStripe();
        $user = User::factory()->create(['role' => 'USER', 'tokens' => 0]);
        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'MEMBER'] + self::ADDRESS)->assertOk();

        $event = ['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_m1', 'latest_charge' => 'ch_m1']]];
        $this->signedWebhook($event)->assertOk();
        $this->signedWebhook($event)->assertOk();

        $user->refresh();
        $this->assertSame('MEMBER', $user->role);
        $this->assertSame(20, $user->tokens);
        $this->assertSame(20, $user->bonus_tokens);
    }

    public function test_renewal_extends_from_expiry_and_grants_package_to_members(): void
    {
        Notification::fake();
        $this->fakeStripe();
        $expires = now()->addMonths(2);
        $user = User::factory()->member()->create(['tokens' => 3, 'membership_expires_at' => $expires]);

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertOk();
        $this->actingAs($user)->postJson('/api/membership/confirm/pi_m1')->assertOk();

        $user->refresh();
        $this->assertEqualsWithDelta($expires->copy()->addYear()->timestamp, $user->membership_expires_at->timestamp, 5);
        $this->assertSame(23, $user->tokens);
        $this->assertSame(20, $user->bonus_tokens);
    }

    public function test_renewal_is_rejected_more_than_three_months_before_expiry(): void
    {
        $this->fakeStripe();
        $user = User::factory()->member()->create(['membership_expires_at' => now()->addMonths(6)]);

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertUnprocessable();
    }

    public function test_supporter_renewal_grants_no_tokens(): void
    {
        $this->fakeStripe();
        $user = User::factory()->supporter()->create(['membership_expires_at' => now()->addMonth()]);

        $this->actingAs($user)->postJson('/api/membership/checkout', ['type' => 'RENEWAL'])->assertOk();
        $this->actingAs($user)->postJson('/api/membership/confirm/pi_m1')->assertOk();

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
            ->assertJsonPath('user.tokens', 20)
            ->assertJsonPath('user.bonus_tokens', 20);
    }

    public function test_refunded_charge_marks_membership_payment(): void
    {
        $user = User::factory()->create();
        MembershipPayment::create([
            'user_id' => $user->id, 'type' => 'MEMBER', 'provider_payment_intent_id' => 'pi_x',
            'provider_charge_id' => 'ch_x', 'price_cents' => 2400, 'status' => 'COMPLETED',
        ]);

        $this->signedWebhook(['type' => 'charge.refunded', 'data' => ['object' => ['id' => 'ch_x']]])->assertOk();

        $this->assertDatabaseHas('membership_payments', ['provider_charge_id' => 'ch_x', 'status' => 'REFUNDED']);
    }
}

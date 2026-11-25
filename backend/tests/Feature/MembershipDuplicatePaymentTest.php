<?php

namespace Tests\Feature;

use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\MembershipPaymentDuplicate;
use App\Notifications\MembershipPaymentDuplicateAdmin;
use App\Notifications\WelcomeMemberNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Nobody pays for the same membership twice: a second click continues the
 * open payment, and a second payment that still gets through is refunded
 * instead of applied.
 */
class MembershipDuplicatePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = ['street' => 'Hauptstraße 1', 'postal_code' => '1010', 'city' => 'Wien'];

    /** @var array<string, array{status: string, isCancelable: bool, refunded?: string}> Mollie's view of each payment */
    private array $mollie = [];

    /** Refunds the app sent itself — must stay 0: money goes back by hand. */
    private int $refundCalls = 0;

    /** @var list<string> */
    private array $cancels = [];

    private bool $mollieDown = false;

    private int $next = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['services.mollie.key' => 'test_dummy', 'services.mollie.webhook_url' => 'https://api.example.test/api/webhooks/mollie']);

        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if ($request->method() === 'POST' && $path === '/v2/payments') {
                $id = 'tr_'.$this->next++;
                $this->mollie[$id] = ['status' => 'open', 'isCancelable' => false];

                return Http::response(['id' => $id, 'status' => 'open', '_links' => ['checkout' => ['href' => "https://www.mollie.com/checkout/{$id}"]]], 201);
            }
            if (str_ends_with($path, '/refunds')) {
                $this->refundCalls++;

                return Http::response(['id' => 're_1', 'status' => 'pending'], 201);
            }
            if (preg_match('#^/v2/payments/(tr_\w+)$#', $path, $m)) {
                if ($this->mollieDown) {
                    return Http::response(['detail' => 'down'], 503);
                }
                if ($request->method() === 'DELETE') {
                    $this->cancels[] = $m[1];
                    $this->mollie[$m[1]]['status'] = 'canceled';

                    return Http::response(['id' => $m[1], 'status' => 'canceled']);
                }
                $payment = $this->mollie[$m[1]];

                return Http::response([
                    'id' => $m[1],
                    'status' => $payment['status'],
                    'isCancelable' => $payment['isCancelable'],
                    ...isset($payment['refunded']) ? ['amountRefunded' => ['currency' => 'EUR', 'value' => $payment['refunded']]] : [],
                    '_links' => $payment['status'] === 'open' ? ['checkout' => ['href' => "https://www.mollie.com/checkout/{$m[1]}"]] : [],
                ]);
            }

            return Http::response([], 404);
        });
    }

    private function checkout(User $user, string $type): TestResponse
    {
        $body = $type === 'RENEWAL' ? ['type' => $type] : ['type' => $type] + self::ADDRESS;

        return $this->actingAs($user)->postJson('/api/membership/checkout', $body);
    }

    private function paid(string $id): void
    {
        $this->mollie[$id]['status'] = 'paid';
        $this->post('/api/webhooks/mollie', ['id' => $id])->assertOk();
    }

    // ── Second click before paying ───────────────────────────────

    public function test_second_click_continues_the_open_payment(): void
    {
        $user = User::factory()->create(['role' => 'USER']);

        $first = $this->checkout($user, 'MEMBER')->assertOk()->json('checkoutUrl');
        $this->checkout($user, 'MEMBER')
            ->assertOk()
            ->assertJsonPath('checkoutUrl', $first)
            ->assertJsonPath('resumed', true);

        $this->assertSame(1, MembershipPayment::count());
    }

    public function test_payment_that_went_through_meanwhile_is_booked_and_blocks_a_new_one(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $this->checkout($user, 'MEMBER')->assertOk();
        $this->mollie['tr_1']['status'] = 'paid'; // webhook not there yet

        $this->checkout($user, 'MEMBER')->assertUnprocessable();

        $this->assertSame('MEMBER', $user->fresh()?->role);
        $this->assertSame(1, MembershipPayment::count());
    }

    public function test_switching_type_cancels_the_open_payment_where_mollie_allows(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $this->checkout($user, 'SUPPORTER')->assertOk();
        $this->mollie['tr_1']['isCancelable'] = true;

        $this->checkout($user, 'MEMBER')->assertOk()->assertJsonPath('checkoutUrl', 'https://www.mollie.com/checkout/tr_2');

        $this->assertSame(['tr_1'], $this->cancels);
        $this->assertSame('FAILED', MembershipPayment::where('provider_payment_id', 'tr_1')->value('status'));
    }

    public function test_switching_type_asks_to_finish_an_uncancelable_payment_first(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $this->checkout($user, 'SUPPORTER')->assertOk();

        $this->checkout($user, 'MEMBER')
            ->assertStatus(409)
            ->assertJsonPath('reason', 'payment_open')
            ->assertJsonPath('checkoutUrl', 'https://www.mollie.com/checkout/tr_1');

        $this->assertSame(1, MembershipPayment::count());
    }

    public function test_mollie_being_unreachable_does_not_block_the_checkout(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $this->checkout($user, 'MEMBER')->assertOk();

        $this->mollieDown = true;
        $this->checkout($user, 'MEMBER')->assertOk()->assertJsonPath('checkoutUrl', 'https://www.mollie.com/checkout/tr_2');
    }

    // ── Second payment that still went through ───────────────────

    /** Two tabs: both checkouts open before either was paid. */
    private function twoOpenCheckouts(User $user, string $type): void
    {
        $this->checkout($user, $type)->assertOk();
        $this->mollie['tr_1']['status'] = 'canceled'; // hides it from the guard for a moment
        $this->checkout($user, $type)->assertOk();
        $this->mollie['tr_1']['status'] = 'open';
        MembershipPayment::where('provider_payment_id', 'tr_1')->update(['status' => 'CREATED']);
    }

    private function statusOf(string $id): ?string
    {
        return MembershipPayment::where('provider_payment_id', $id)->value('status');
    }

    private function fee(): string
    {
        return number_format(config()->integer('membership.fee_cents') / 100, 2, '.', '');
    }

    public function test_second_new_membership_payment_is_not_applied_and_waits_for_a_manual_refund(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'USER']);
        $this->twoOpenCheckouts($user, 'MEMBER');

        $this->paid('tr_1');
        $this->paid('tr_2');

        $this->assertSame('COMPLETED', $this->statusOf('tr_1'));
        $this->assertSame(MembershipPayment::STATUS_DUPLICATE, $this->statusOf('tr_2'));
        $this->assertSame(0, $this->refundCalls, 'no money moves on its own');

        $user->refresh();
        $this->assertSame(20, $user->bonus_tokens, 'bonus package once, not twice');
        Notification::assertSentToTimes($user, WelcomeMemberNotification::class, 1);
        Notification::assertSentToTimes($user, MembershipPaymentDuplicate::class, 1);
        Notification::assertSentToTimes($admin, MembershipPaymentDuplicateAdmin::class, 1);

        // webhook retries and the return page polling tell nobody twice
        $this->paid('tr_2');
        Notification::assertSentToTimes($admin, MembershipPaymentDuplicateAdmin::class, 1);

        $id = MembershipPayment::where('provider_payment_id', 'tr_2')->value('id');
        $this->actingAs($user)->getJson("/api/payments/membership/{$id}")
            ->assertOk()
            ->assertJsonPath('status', 'duplicate')
            ->assertJsonPath('message', fn (string $m) => str_contains($m, 'doppelt gezahlt'));
    }

    public function test_second_renewal_extends_only_once(): void
    {
        $expires = now()->addMonths(2);
        $user = User::factory()->member()->create(['membership_expires_at' => $expires]);
        $this->twoOpenCheckouts($user, 'RENEWAL');

        $this->paid('tr_1');
        $this->paid('tr_2');

        $this->assertEqualsWithDelta($expires->copy()->addYear()->timestamp, $user->fresh()?->membership_expires_at?->timestamp, 5);
        $this->assertSame(MembershipPayment::STATUS_DUPLICATE, $this->statusOf('tr_2'));
    }

    public function test_admin_list_shows_open_refunds_until_marked(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'USER', 'name' => 'Mia']);
        $this->twoOpenCheckouts($user, 'MEMBER');
        $this->paid('tr_1');
        $this->paid('tr_2');

        $row = $this->actingAs($admin)->getJson('/api/admin/duplicate-payments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.name', 'Mia')
            ->assertJsonPath('data.0.provider_payment_id', 'tr_2')
            ->json('data.0');

        $this->actingAs($admin)->patchJson("/api/admin/duplicate-payments/{$row['id']}/refunded")->assertOk();
        $this->actingAs($admin)->patchJson("/api/admin/duplicate-payments/{$row['id']}/refunded")->assertUnprocessable();
        $this->actingAs($admin)->getJson('/api/admin/duplicate-payments')->assertJsonCount(0, 'data');
        $this->assertSame(MembershipPayment::STATUS_DUP_REFUNDED, $this->statusOf('tr_2'));

        $this->actingAs($user)->getJson('/api/admin/duplicate-payments')->assertForbidden();
    }

    public function test_refund_made_in_the_mollie_dashboard_ticks_itself_off(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $this->twoOpenCheckouts($user, 'MEMBER');
        $this->paid('tr_1');
        $this->paid('tr_2');

        $this->mollie['tr_2']['refunded'] = $this->fee();
        $this->post('/api/webhooks/mollie', ['id' => 'tr_2'])->assertOk();

        $this->assertSame(MembershipPayment::STATUS_DUP_REFUNDED, $this->statusOf('tr_2'));
        $this->assertSame('MEMBER', $user->fresh()?->role, 'refunding the duplicate leaves the membership alone');
        $this->assertSame('COMPLETED', $this->statusOf('tr_1'));
    }
}

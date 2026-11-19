<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\Review;
use App\Models\User;
use App\Notifications\InactiveAccountNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InactiveAccountTest extends TestCase
{
    use RefreshDatabase;

    private function staleUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'last_seen_at' => now()->subYears(3)->subDay(),
            'membership_expires_at' => null,
            'tokens' => 0,
        ], $attrs));
    }

    public function test_inactive_account_gets_a_notice_first(): void
    {
        Notification::fake();
        $user = $this->staleUser();

        $this->artisan('accounts:prune-inactive')->assertSuccessful();

        Notification::assertSentTo($user, InactiveAccountNotice::class);
        $user->refresh();
        $this->assertNotNull($user->inactivity_notice_sent_at);
        $this->assertNull($user->anonymized_at);
    }

    public function test_notice_is_sent_only_once(): void
    {
        Notification::fake();
        $user = $this->staleUser(['inactivity_notice_sent_at' => now()->subDays(5)]);

        $this->artisan('accounts:prune-inactive')->assertSuccessful();

        Notification::assertNothingSentTo($user);
        $this->assertNull($user->refresh()->anonymized_at);
    }

    public function test_account_is_anonymized_after_grace_period_and_records_are_kept(): void
    {
        Notification::fake();
        $user = $this->staleUser([
            'inactivity_notice_sent_at' => now()->subDays(31),
            'street' => 'Teststraße 1',
            'postal_code' => '3100',
            'city' => 'St. Pölten',
            'date_of_birth' => '1990-01-01',
            'newsletter_opt_in' => true,
        ]);
        $originalEmail = $user->email;
        $loan = Loan::factory()->returned()->create(['user_id' => $user->id]);
        Review::factory()->create(['user_id' => $user->id]);
        Favorite::factory()->create(['user_id' => $user->id]);
        EmailLog::create(['user_id' => $user->id, 'recipient_email' => $originalEmail, 'template_key' => 'x', 'subject' => 'x', 'sent_at' => now()]);
        $user->createToken('spa');

        $this->artisan('accounts:prune-inactive')->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->anonymized_at);
        $this->assertSame('Gelöschtes Konto', $user->name);
        $this->assertSame("deleted-{$user->id}@anonymized.invalid", $user->email);
        $this->assertNull($user->street);
        $this->assertNull($user->postal_code);
        $this->assertNull($user->city);
        $this->assertNull($user->date_of_birth);
        $this->assertFalse($user->newsletter_opt_in);
        $this->assertSame('SUSPENDED', $user->status);

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('reviews', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('email_logs', ['recipient_email' => $originalEmail]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);

        $this->postJson('/api/auth/login', ['email' => $originalEmail, 'password' => 'password'])
            ->assertUnauthorized();
    }

    public function test_activity_during_grace_period_cancels_the_deletion(): void
    {
        Notification::fake();
        $user = $this->staleUser(['inactivity_notice_sent_at' => now()->subDays(31)]);

        $this->actingAs($user)->getJson('/api/auth/me')->assertOk();
        $this->artisan('accounts:prune-inactive')->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->anonymized_at);
        $this->assertNull($user->inactivity_notice_sent_at);
        $this->assertTrue($user->last_seen_at->isToday());
    }

    public function test_login_records_activity(): void
    {
        $user = $this->staleUser(['inactivity_notice_sent_at' => now()->subDays(3)]);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        $user->refresh();
        $this->assertTrue($user->last_seen_at->isToday());
        $this->assertNull($user->inactivity_notice_sent_at);
    }

    public function test_recently_active_accounts_are_untouched(): void
    {
        Notification::fake();
        $user = User::factory()->create(['last_seen_at' => now()->subYears(2)]);

        $this->artisan('accounts:prune-inactive')->assertSuccessful();

        Notification::assertNothingSentTo($user);
    }

    public function test_accounts_with_open_obligations_or_value_are_skipped(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create(['last_seen_at' => now()->subYears(4)]);
        $withTokens = $this->staleUser(['tokens' => 10]);
        $withMembership = $this->staleUser(['membership_expires_at' => now()->addMonth()]);
        $withLoan = $this->staleUser();
        Loan::factory()->create(['user_id' => $withLoan->id, 'status' => 'OVERDUE']);

        $this->artisan('accounts:prune-inactive')
            ->expectsOutputToContain('review manually')
            ->assertSuccessful();

        Notification::assertNothingSent();
        foreach ([$admin, $withTokens, $withMembership, $withLoan] as $user) {
            $this->assertNull($user->refresh()->inactivity_notice_sent_at);
        }
    }

    public function test_dry_run_changes_nothing(): void
    {
        Notification::fake();
        $user = $this->staleUser(['inactivity_notice_sent_at' => now()->subDays(31)]);
        $fresh = $this->staleUser();

        $this->artisan('accounts:prune-inactive', ['--dry-run' => true])->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($user->refresh()->anonymized_at);
        $this->assertNull($fresh->refresh()->inactivity_notice_sent_at);
    }

    public function test_email_logs_older_than_twelve_months_are_pruned(): void
    {
        $old = EmailLog::create(['recipient_email' => 'a@example.at', 'template_key' => 'x', 'subject' => 'x', 'sent_at' => now()->subMonths(12)->subDay()]);
        $recent = EmailLog::create(['recipient_email' => 'b@example.at', 'template_key' => 'x', 'subject' => 'x', 'sent_at' => now()->subMonths(11)]);

        $this->artisan('model:prune', ['--model' => [EmailLog::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}

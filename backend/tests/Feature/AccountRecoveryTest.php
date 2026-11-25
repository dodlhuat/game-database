<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The ways back into an account: confirming the e-mail address and resetting
 * the password. Each flow is driven through the link the mail actually
 * contains, so a broken link fails here and not in a member's inbox.
 */
class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function pendingUser(): User
    {
        return User::factory()->create([
            'email' => 'mia@example.at',
            'status' => 'PENDING',
            'email_verified_at' => null,
        ]);
    }

    private function frontend(string $path): string
    {
        return config()->string('frontend.url').$path;
    }

    // ── E-mail verification ──────────────────────────────────────

    public function test_link_from_the_verification_mail_activates_the_account(): void
    {
        $user = $this->pendingUser();

        $link = (new VerifyEmailNotification)->toMail($user)->actionUrl;
        $this->get($link)->assertRedirect($this->frontend('/email-verified?success=1'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('ACTIVE', $user->status);
    }

    public function test_tampered_or_expired_links_are_refused(): void
    {
        $user = $this->pendingUser();
        $link = URL::temporarySignedRoute('auth.verify-email', now()->addHour(), ['id' => $user->id]);

        $this->get($link.'x')->assertRedirect($this->frontend('/email-verified?error=invalid_link'));
        $other = User::factory()->create(['email_verified_at' => null]);
        $this->get(str_replace("/{$user->id}?", "/{$other->id}?", $link))
            ->assertRedirect($this->frontend('/email-verified?error=invalid_link'));

        $this->travel(2)->hours();
        $this->get($link)->assertRedirect($this->frontend('/email-verified?error=invalid_link'));

        $this->assertNull($user->fresh()?->email_verified_at);
    }

    public function test_second_click_reports_already_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $link = URL::temporarySignedRoute('auth.verify-email', now()->addHour(), ['id' => $user->id]);

        $this->get($link)->assertRedirect($this->frontend('/email-verified?already=1'));
    }

    public function test_resend_mails_only_unverified_accounts_and_reveals_nothing(): void
    {
        Notification::fake();
        $pending = $this->pendingUser();
        $verified = User::factory()->create(['email' => 'done@example.at', 'email_verified_at' => now()]);

        $answers = collect(['mia@example.at', 'done@example.at', 'nobody@example.at'])
            ->map(fn (string $email) => $this->postJson('/api/auth/email/resend', ['email' => $email])->assertOk()->json('message'));

        $this->assertCount(1, $answers->unique(), 'same answer whether the address exists or not');
        Notification::assertSentTo($pending, VerifyEmailNotification::class);
        Notification::assertNotSentTo($verified, VerifyEmailNotification::class);
    }

    // ── Password reset ───────────────────────────────────────────

    public function test_link_from_the_reset_mail_sets_a_new_password_and_ends_old_sessions(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'mia@example.at', 'password' => 'altes-passwort']);
        $user->createToken('spa');

        $this->postJson('/api/auth/forgot-password', ['email' => 'mia@example.at'])->assertOk();

        $link = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $mail) use ($user, &$link) {
            $link = $mail->toMail($user)->actionUrl;

            return true;
        });
        parse_str((string) parse_url((string) $link, PHP_URL_QUERY), $query);
        $this->assertStringStartsWith($this->frontend('/reset-password?'), (string) $link);
        $this->assertSame('mia@example.at', $query['email']);

        $this->postJson('/api/auth/reset-password', [
            'token' => $query['token'],
            'email' => $query['email'],
            'password' => 'neues-passwort-123',
            'password_confirmation' => 'neues-passwort-123',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('neues-passwort-123', $user->password));
        $this->assertSame(0, $user->tokens()->count(), 'old logins are ended');
        $this->postJson('/api/auth/login', ['email' => 'mia@example.at', 'password' => 'neues-passwort-123'])
            ->assertOk()->assertJsonStructure(['token']);
    }

    public function test_a_reset_link_works_only_once(): void
    {
        $user = User::factory()->create(['email' => 'mia@example.at']);
        $token = Password::createToken($user);
        $body = ['token' => $token, 'email' => 'mia@example.at', 'password' => 'neues-passwort-123', 'password_confirmation' => 'neues-passwort-123'];

        $this->postJson('/api/auth/reset-password', $body)->assertOk();
        $this->postJson('/api/auth/reset-password', $body)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Dieser Link ist ungültig oder bereits abgelaufen.');
    }

    public function test_wrong_token_and_weak_or_unconfirmed_passwords_are_refused(): void
    {
        $user = User::factory()->create(['email' => 'mia@example.at', 'password' => 'altes-passwort']);
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => 'falsch', 'email' => 'mia@example.at',
            'password' => 'neues-passwort-123', 'password_confirmation' => 'neues-passwort-123',
        ])->assertUnprocessable()->assertJsonPath('message', 'Dieser Link ist ungültig oder bereits abgelaufen.');

        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => 'mia@example.at',
            'password' => 'kurz', 'password_confirmation' => 'kurz',
        ])->assertJsonValidationErrors('password');

        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => 'mia@example.at',
            'password' => 'neues-passwort-123', 'password_confirmation' => 'anders-123',
        ])->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('altes-passwort', $user->fresh()?->password ?? ''));
    }
}

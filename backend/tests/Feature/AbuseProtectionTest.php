<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
            'form_loaded_at' => (int) (microtime(true) * 1000) - 5000,
        ], $overrides);
    }

    // ── Login ──────────────────────────────────────────────────────

    public function test_login_is_throttled_per_account_after_five_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Zu viele Versuche. Bitte warte kurz und versuche es dann erneut.');
    }

    public function test_login_limit_for_one_account_does_not_block_another(): void
    {
        $victim = User::factory()->create();
        $other = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => $victim->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/auth/login', ['email' => $other->email, 'password' => 'password'])
            ->assertOk();
    }

    // ── Registration ───────────────────────────────────────────────

    public function test_honeypot_looks_like_success_but_creates_nothing(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', $this->registerPayload(['website' => 'https://spam.example']))
            ->assertCreated()
            ->assertJsonPath('message', 'Registrierung erfolgreich. Bitte bestätige deine E-Mail-Adresse.')
            ->assertJsonStructure(['token', 'user' => ['email', 'status']]);

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
        Notification::assertNothingSent();
    }

    public function test_registration_without_honeypot_still_works(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', $this->registerPayload(['website' => '']))
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'bot@example.com']);
    }

    public function test_registration_is_throttled_per_ip(): void
    {
        Notification::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/register', $this->registerPayload(['email' => "u{$i}@example.com"]))
                ->assertCreated();
        }

        $this->postJson('/api/auth/register', $this->registerPayload(['email' => 'u11@example.com']))
            ->assertStatus(429);
    }

    // ── Email-sending endpoints ────────────────────────────────────

    public function test_password_reset_mails_are_limited_per_address(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(429);
    }

    // ── Address check ──────────────────────────────────────────────

    public function test_address_check_is_throttled_per_user(): void
    {
        Http::fake(['*' => Http::response([['display_name' => 'x']])]);
        Sleep::fake();
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson('/api/address/validate', [
                'street' => "Teststraße {$i}", 'postal_code' => '3100', 'city' => 'St. Pölten',
            ])->assertOk();
        }

        $this->actingAs($user)->postJson('/api/address/validate', [
            'street' => 'Teststraße 99', 'postal_code' => '3100', 'city' => 'St. Pölten',
        ])->assertStatus(429);
    }

    // ── Session expiry ─────────────────────────────────────────────

    public function test_session_unused_for_30_days_no_longer_works(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('spa')->plainTextToken;
        PersonalAccessToken::query()->update(['last_used_at' => now()->subDays(31)]);

        $this->withToken($plain)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_session_used_recently_keeps_working(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('spa')->plainTextToken;
        PersonalAccessToken::query()->update([
            'created_at' => now()->subDays(200),
            'last_used_at' => now()->subDays(29),
        ]);

        $this->withToken($plain)->getJson('/api/auth/me')->assertOk();
    }

    public function test_idle_sessions_are_pruned(): void
    {
        $user = User::factory()->create();
        $user->createToken('idle');
        $user->createToken('fresh');
        PersonalAccessToken::where('name', 'idle')->update(['last_used_at' => now()->subDays(31)]);
        PersonalAccessToken::where('name', 'fresh')->update(['last_used_at' => now()->subDay()]);

        $this->artisan('auth:prune-idle-sessions')->assertSuccessful();

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'idle']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'fresh']);
    }
}

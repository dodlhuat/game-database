<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Who may log in: the right password alone is not enough. */
class LoginStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function blocked(): array
    {
        return [
            'e-mail not confirmed' => [['email_verified_at' => null, 'status' => 'PENDING'], 'reason'],
            'waiting for approval' => [['status' => 'PENDING'], 'status'],
            'registration rejected' => [['status' => 'REJECTED'], 'status'],
            'suspended' => [['status' => 'SUSPENDED'], 'status'],
        ];
    }

    /** @param  array<string, mixed>  $state */
    #[DataProvider('blocked')]
    public function test_blocked_accounts_get_no_token(array $state, string $field): void
    {
        User::factory()->create(['email' => 'mia@example.at', 'password' => 'geheim-123', ...$state]);

        $response = $this->postJson('/api/auth/login', ['email' => 'mia@example.at', 'password' => 'geheim-123'])
            ->assertForbidden()
            ->assertJsonMissingPath('token');

        $this->assertNotNull($response->json($field), 'the frontend needs to know why');
        $this->assertSame(0, User::where('email', 'mia@example.at')->firstOrFail()->tokens()->count());
    }

    public function test_an_active_confirmed_account_logs_in(): void
    {
        User::factory()->create(['email' => 'mia@example.at', 'password' => 'geheim-123', 'status' => 'ACTIVE']);

        $this->postJson('/api/auth/login', ['email' => 'mia@example.at', 'password' => 'geheim-123'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }
}

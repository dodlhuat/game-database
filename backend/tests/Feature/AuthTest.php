<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
            'form_loaded_at' => (int) (microtime(true) * 1000) - 4000,
        ], $overrides);
    }

    public function test_register_creates_user(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload([
            'email' => 'test@example.com',
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', $this->registerPayload([
            'email' => 'taken@example.com',
        ]))->assertStatus(422);
    }

    public function test_register_fails_if_loaded_too_recently(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload([
            'form_loaded_at' => (int) (microtime(true) * 1000) - 100, // only 100ms ago
        ]))->assertStatus(422);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_login_is_blocked_for_non_admins_in_maintenance_mode(): void
    {
        config(['app.maintenance_mode' => true]);
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'member@example.com',
            'password' => 'password123',
        ])->assertStatus(503)->assertJson(['reason' => 'maintenance']);
    }

    public function test_login_still_works_for_admins_in_maintenance_mode(): void
    {
        config(['app.maintenance_mode' => true]);
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_with_wrong_password_reveals_nothing_in_maintenance_mode(): void
    {
        config(['app.maintenance_mode' => true]);
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('correct'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'member@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_register_is_blocked_in_maintenance_mode(): void
    {
        config(['app.maintenance_mode' => true]);

        $this->postJson('/api/auth/register', $this->registerPayload([
            'email' => 'new@example.com',
        ]))->assertStatus(503)->assertJson(['reason' => 'maintenance']);

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_existing_non_admin_login_is_ended_in_maintenance_mode(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;
        config(['app.maintenance_mode' => true]);

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson(['reason' => 'maintenance']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_existing_admin_login_keeps_working_in_maintenance_mode(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $token = $admin->createToken('spa')->plainTextToken;
        config(['app.maintenance_mode' => true]);

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();
        $this->withToken($token)->getJson('/api/admin/users')->assertOk();
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_forgot_password_returns_ok_for_existing_email(): void
    {
        User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk();
    }

    public function test_forgot_password_returns_ok_for_unknown_email(): void
    {
        // Should not reveal whether email exists
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ])->assertOk();
    }
}

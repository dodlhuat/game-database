<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_requires_auth(): void
    {
        $this->postJson('/api/tokens/add', ['amount' => 20])->assertUnauthorized();
    }

    public function test_add_increases_tokens_for_member(): void
    {
        $user = User::factory()->member()->create(['tokens' => 5]);

        $this->actingAs($user)
            ->postJson('/api/tokens/add', ['amount' => 20])
            ->assertOk()
            ->assertJsonPath('user.tokens', 25);
    }

    public function test_add_increases_tokens_for_admin(): void
    {
        $admin = User::factory()->admin()->create(['tokens' => 0]);

        $this->actingAs($admin)
            ->postJson('/api/tokens/add', ['amount' => 30])
            ->assertOk()
            ->assertJsonPath('user.tokens', 30);
    }

    public function test_add_fails_for_non_member(): void
    {
        $user = User::factory()->create(['role' => 'USER', 'status' => 'ACTIVE']);

        $this->actingAs($user)
            ->postJson('/api/tokens/add', ['amount' => 20])
            ->assertStatus(403);
    }

    public function test_add_rejects_invalid_amount(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/tokens/add', ['amount' => 15])
            ->assertStatus(422);
    }
}

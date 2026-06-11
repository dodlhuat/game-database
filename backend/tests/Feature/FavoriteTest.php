<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_favorited_games(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();
        Favorite::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_store_adds_favorite(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/favorites', ['game_id' => $game->id])
            ->assertOk();

        $this->assertDatabaseHas('favorites', ['game_id' => $game->id, 'user_id' => $user->id]);
    }

    public function test_store_is_idempotent(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();
        Favorite::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->postJson('/api/favorites', ['game_id' => $game->id])
            ->assertOk();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_destroy_removes_favorite(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();
        Favorite::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->deleteJson("/api/favorites/{$game->id}")
            ->assertOk();

        $this->assertDatabaseMissing('favorites', ['game_id' => $game->id, 'user_id' => $user->id]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/favorites')->assertUnauthorized();
    }
}

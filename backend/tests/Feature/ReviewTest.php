<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_review(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/reviews', [
                'game_id' => $game->id,
                'rating' => 4,
                'comment' => 'Great game!',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('reviews', ['game_id' => $game->id, 'user_id' => $user->id]);
    }

    public function test_store_prevents_duplicate_review(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();
        Review::factory()->create(['game_id' => $game->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/reviews', [
                'game_id' => $game->id,
                'rating' => 3,
            ])
            ->assertStatus(422);
    }

    public function test_update_modifies_own_review(): void
    {
        $user = User::factory()->member()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'rating' => 3]);

        $this->actingAs($user)
            ->putJson("/api/reviews/{$review->id}", ['rating' => 5])
            ->assertOk()
            ->assertJsonPath('data.rating', 5);
    }

    public function test_update_denies_other_users_review(): void
    {
        $user = User::factory()->member()->create();
        $review = Review::factory()->create(); // other user

        $this->actingAs($user)
            ->putJson("/api/reviews/{$review->id}", ['rating' => 1])
            ->assertForbidden();
    }

    public function test_destroy_deletes_own_review(): void
    {
        $user = User::factory()->member()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/reviews/{$review->id}")
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_destroy_denies_other_users_review(): void
    {
        $user = User::factory()->member()->create();
        $review = Review::factory()->create(); // other user

        $this->actingAs($user)
            ->deleteJson("/api/reviews/{$review->id}")
            ->assertForbidden();
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/reviews', ['game_id' => 1, 'rating' => 4])
            ->assertUnauthorized();
    }
}

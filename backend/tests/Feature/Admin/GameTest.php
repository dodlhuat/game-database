<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function gamePayload(array $overrides = []): array
    {
        $slug = 'test-game-' . uniqid();
        return array_merge([
            'title'      => 'Test Game',
            'slug'       => $slug,
            'is_active'  => true,
            'category_id' => Category::factory()->create()->id,
        ], $overrides);
    }

    public function test_index_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/games')
            ->assertForbidden();
    }

    public function test_index_returns_all_games(): void
    {
        Game::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/games')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_store_creates_game(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/games', $this->gamePayload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Test Game');
    }

    public function test_store_requires_unique_slug(): void
    {
        Game::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($this->admin())
            ->postJson('/api/admin/games', $this->gamePayload(['slug' => 'existing-slug']))
            ->assertStatus(422);
    }

    public function test_show_returns_game(): void
    {
        $game = Game::factory()->create();

        $this->actingAs($this->admin())
            ->getJson("/api/admin/games/{$game->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $game->id);
    }

    public function test_update_modifies_game(): void
    {
        $game = Game::factory()->create();

        $this->actingAs($this->admin())
            ->putJson("/api/admin/games/{$game->id}", $this->gamePayload([
                'slug'  => $game->slug,
                'title' => 'Updated Title',
            ]))
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_destroy_deletes_game(): void
    {
        $game = Game::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson("/api/admin/games/{$game->id}")
            ->assertOk();

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }

    public function test_index_includes_inactive_games(): void
    {
        Game::factory()->inactive()->create(['title' => 'Inactive Game']);

        $response = $this->actingAs($this->admin())
            ->getJson('/api/admin/games')
            ->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Inactive Game', $titles->all());
    }
}

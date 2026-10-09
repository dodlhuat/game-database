<?php

namespace Tests\Feature\Admin;

use App\Models\Game;
use App\Models\Language;
use App\Models\Mechanic;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $slug = 'test-game-'.uniqid();

        return array_merge([
            'title' => 'Test Game',
            'slug' => $slug,
            'is_active' => true,
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

    public function test_store_downscales_large_cover_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())
            ->post('/api/admin/games', array_merge($this->gamePayload(), [
                'cover_image' => UploadedFile::fake()->image('cover.jpg', 3000, 4000),
            ]))
            ->assertCreated();

        $url = $response->json('data.cover_image_url');
        $this->assertStringEndsWith('.jpg', $url);

        $path = 'covers/'.basename((string) $url);
        Storage::disk('public')->assertExists($path);

        $stored = Storage::disk('public')->get($path);
        $size = getimagesizefromstring((string) $stored);
        $this->assertNotFalse($size);
        $this->assertLessThanOrEqual(1200, max($size[0], $size[1]));
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
                'slug' => $game->slug,
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

    public function test_store_syncs_tags_mechanics_and_languages(): void
    {
        $tags = Tag::factory()->count(2)->create();
        $mechanic = Mechanic::factory()->create();
        $language = Language::factory()->create();

        $id = $this->actingAs($this->admin())
            ->postJson('/api/admin/games', $this->gamePayload([
                'tag_ids' => $tags->pluck('id')->all(),
                'mechanic_ids' => [$mechanic->id],
                'language_ids' => [$language->id],
            ]))
            ->assertCreated()
            ->json('data.id');

        $game = Game::findOrFail($id);
        $this->assertEqualsCanonicalizing($tags->pluck('id')->all(), $game->tags->pluck('id')->all());
        $this->assertSame([$mechanic->id], $game->mechanics->pluck('id')->all());
        $this->assertSame([$language->id], $game->languages->pluck('id')->all());
    }

    public function test_store_without_relation_ids_creates_game_without_relations(): void
    {
        $id = $this->actingAs($this->admin())
            ->postJson('/api/admin/games', $this->gamePayload())
            ->assertCreated()
            ->json('data.id');

        $game = Game::findOrFail($id);
        $this->assertCount(0, $game->tags);
        $this->assertCount(0, $game->mechanics);
        $this->assertCount(0, $game->languages);
    }

    public function test_update_replaces_relation_ids_and_empty_list_clears_them(): void
    {
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $oldMechanic = Mechanic::factory()->create();
        $newMechanic = Mechanic::factory()->create();
        $oldLanguage = Language::factory()->create();
        $newLanguage = Language::factory()->create();
        $game = Game::factory()->create();
        $game->tags()->attach($oldTag);
        $game->mechanics()->attach($oldMechanic);
        $game->languages()->attach($oldLanguage);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/games/{$game->id}", $this->gamePayload([
                'slug' => $game->slug,
                'tag_ids' => [$newTag->id],
                'mechanic_ids' => [$newMechanic->id],
                'language_ids' => [$newLanguage->id],
            ]))
            ->assertOk();

        $game->refresh();
        $this->assertSame([$newTag->id], $game->tags->pluck('id')->all());
        $this->assertSame([$newMechanic->id], $game->mechanics->pluck('id')->all());
        $this->assertSame([$newLanguage->id], $game->languages->pluck('id')->all());

        $this->putJson("/api/admin/games/{$game->id}", $this->gamePayload([
            'slug' => $game->slug,
            'tag_ids' => [],
            'mechanic_ids' => [],
            'language_ids' => [],
        ]))->assertOk();

        $game->refresh();
        $this->assertCount(0, $game->tags);
        $this->assertCount(0, $game->mechanics);
        $this->assertCount(0, $game->languages);
    }

    public function test_update_without_relation_keys_keeps_existing_relations(): void
    {
        $tag = Tag::factory()->create();
        $language = Language::factory()->create();
        $game = Game::factory()->create();
        $game->tags()->attach($tag);
        $game->languages()->attach($language);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/games/{$game->id}", $this->gamePayload(['slug' => $game->slug, 'title' => 'Renamed']))
            ->assertOk();

        $game->refresh();
        $this->assertSame([$tag->id], $game->tags->pluck('id')->all());
        $this->assertSame([$language->id], $game->languages->pluck('id')->all());
    }

    public function test_index_search_filters_by_title_and_empty_search_returns_all(): void
    {
        Game::factory()->create(['title' => 'Catan']);
        Game::factory()->create(['title' => 'Azul']);
        $admin = $this->admin();

        $titles = fn (string $query): array => collect(
            $this->actingAs($admin)->getJson('/api/admin/games?'.$query)->assertOk()->json('data')
        )->pluck('title')->all();

        $this->assertSame(['Catan'], $titles('search=cat'));
        $this->assertEqualsCanonicalizing(['Catan', 'Azul'], $titles('search='));
    }
}

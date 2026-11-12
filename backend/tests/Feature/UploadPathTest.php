<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadPathTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://alle-unsere-abenteuer.at/storage';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public', ['url' => self::BASE]);
    }

    public function test_uploaded_cover_is_stored_as_path_and_served_as_url(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/api/admin/games', [
                'title' => 'Catan',
                'slug' => 'catan',
                'size_category' => 'MEDIUM',
                'is_active' => true,
                'cover_image' => UploadedFile::fake()->image('cover.jpg', 100, 100),
            ])
            ->assertCreated();

        $stored = DB::table('games')->where('slug', 'catan')->value('cover_image_url');
        $this->assertIsString($stored);
        $this->assertStringStartsWith('covers/', $stored);
        Storage::disk('public')->assertExists($stored);
        $response->assertJsonPath('data.cover_image_url', self::BASE.'/'.$stored);
    }

    public function test_url_follows_the_current_domain(): void
    {
        $game = Game::factory()->create(['cover_image_url' => 'covers/a.jpg']);

        $this->assertSame(self::BASE.'/covers/a.jpg', $game->fresh()?->cover_image_url);

        Storage::fake('public', ['url' => 'https://new-domain.example/storage']);
        $this->assertSame('https://new-domain.example/storage/covers/a.jpg', $game->fresh()?->cover_image_url);
    }

    public function test_external_urls_pass_through_unchanged(): void
    {
        $game = Game::factory()->create(['cover_image_url' => 'https://picsum.photos/seed/catan/400/560']);

        $this->assertSame('https://picsum.photos/seed/catan/400/560', DB::table('games')->where('id', $game->id)->value('cover_image_url'));
        $this->assertSame('https://picsum.photos/seed/catan/400/560', $game->fresh()?->cover_image_url);
    }

    public function test_a_url_of_this_app_is_stored_as_path(): void
    {
        $game = Game::factory()->create(['cover_image_url' => self::BASE.'/covers/a.jpg']);

        $this->assertSame('covers/a.jpg', DB::table('games')->where('id', $game->id)->value('cover_image_url'));
    }

    public function test_replacing_a_cover_deletes_the_old_file(): void
    {
        Storage::disk('public')->put('covers/old.jpg', 'x');
        $game = Game::factory()->create(['cover_image_url' => 'covers/old.jpg']);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/api/admin/games/{$game->id}", [
                '_method' => 'PUT',
                'title' => $game->title,
                'slug' => $game->slug,
                'size_category' => 'MEDIUM',
                'cover_image' => UploadedFile::fake()->image('new.jpg', 100, 100),
            ])
            ->assertOk();

        Storage::disk('public')->assertMissing('covers/old.jpg');
    }

    public function test_deleting_a_game_removes_its_files(): void
    {
        Storage::disk('public')->put('covers/a.jpg', 'x');
        Storage::disk('public')->put('game-images/b.png', 'x');
        $game = Game::factory()->create(['cover_image_url' => 'covers/a.jpg']);
        GameImage::create(['game_id' => $game->id, 'url' => 'game-images/b.png', 'sort_order' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/admin/games/{$game->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing('covers/a.jpg');
        Storage::disk('public')->assertMissing('game-images/b.png');
    }

    public function test_migration_turns_legacy_urls_into_paths_and_back(): void
    {
        $legacy = Game::factory()->create();
        $external = Game::factory()->create();
        DB::table('games')->where('id', $legacy->id)->update(['cover_image_url' => 'http://65.109.232.115/storage/covers/a.jpg']);
        DB::table('games')->where('id', $external->id)->update(['cover_image_url' => 'https://res.cloudinary.com/demo/image/upload/c.jpg']);

        $migration = require database_path('migrations/2026_11_12_120000_store_upload_paths_instead_of_urls.php');
        $migration->up();

        $this->assertSame('covers/a.jpg', DB::table('games')->where('id', $legacy->id)->value('cover_image_url'));
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/c.jpg', DB::table('games')->where('id', $external->id)->value('cover_image_url'));
        $this->assertSame(self::BASE.'/covers/a.jpg', $legacy->fresh()?->cover_image_url);

        $migration->down();

        $this->assertSame(self::BASE.'/covers/a.jpg', DB::table('games')->where('id', $legacy->id)->value('cover_image_url'));
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/c.jpg', DB::table('games')->where('id', $external->id)->value('cover_image_url'));
    }
}

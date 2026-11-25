<?php

namespace Tests\Feature\Admin;

use App\Models\Game;
use App\Models\GameImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Gallery images of a game: upload in order, delete one by one. */
class GameImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_stores_files_and_appends_them_in_order(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create();
        GameImage::create(['game_id' => $game->id, 'url' => 'games/vorhanden.jpg', 'sort_order' => 4]);

        $this->actingAs($admin)
            ->post("/api/admin/games/{$game->id}/images", [
                'images' => [UploadedFile::fake()->image('a.jpg', 1200, 900), UploadedFile::fake()->image('b.png', 800, 800)],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonCount(2, 'images');

        $new = $game->images()->where('sort_order', '>', 4)->orderBy('sort_order')->get();
        $this->assertSame([5, 6], $new->pluck('sort_order')->all());
        foreach ($new as $image) {
            Storage::disk('public')->assertExists($image->getRawOriginal('url'));
        }
    }

    public function test_only_images_are_accepted(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create())
            ->post('/api/admin/games/'.Game::factory()->create()->id.'/images', [
                'images' => [UploadedFile::fake()->create('notizen.pdf', 20, 'application/pdf')],
            ], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('images.0');
    }

    public function test_delete_removes_record_and_file_but_only_for_its_own_game(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create();
        $other = Game::factory()->create();
        Storage::disk('public')->put('games/bild.jpg', 'x');
        $image = GameImage::create(['game_id' => $game->id, 'url' => 'games/bild.jpg', 'sort_order' => 1]);

        $this->actingAs($admin)->deleteJson("/api/admin/games/{$other->id}/images/{$image->id}")->assertNotFound();
        $this->assertModelExists($image);

        $this->actingAs($admin)->deleteJson("/api/admin/games/{$game->id}/images/{$image->id}")->assertOk();
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('games/bild.jpg');
    }
}

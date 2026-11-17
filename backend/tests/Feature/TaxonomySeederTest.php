<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Mechanic;
use App\Models\Tag;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TaxonomySeederTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/taxonomy-'.uniqid();
        mkdir($this->dir);
        $this->app->instance(TaxonomySeeder::class, new TaxonomySeeder($this->dir));
        // Start from empty tables — migrations may ship a few defaults.
        DB::table('game_mechanics')->delete();
        DB::table('game_tags')->delete();
        Mechanic::query()->delete();
        Tag::query()->delete();
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);
        parent::tearDown();
    }

    private function lists(string $mechanics, string $tags): void
    {
        file_put_contents($this->dir.'/mechanics.txt', $mechanics);
        file_put_contents($this->dir.'/tags.txt', $tags);
    }

    public function test_seeds_both_lists_and_ignores_comments(): void
    {
        $this->lists("# Kommentar\nWorker Placement\n\nDeckbau\n", "Familienspiel\nKooperativ\n");

        $this->seed(TaxonomySeeder::class);

        $this->assertEqualsCanonicalizing(['Worker Placement', 'Deckbau'], Mechanic::pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Familienspiel', 'Kooperativ'], Tag::pluck('name')->all());
        $this->assertSame('worker-placement', Mechanic::where('name', 'Worker Placement')->value('slug'));
    }

    public function test_running_again_keeps_ids_assignments_and_extra_entries(): void
    {
        $this->lists("Deckbau\n", "Kooperativ\n");
        $this->seed(TaxonomySeeder::class);
        $deckbau = Mechanic::firstOrFail();
        $game = Game::factory()->create();
        $game->mechanics()->attach($deckbau->id);
        Tag::create(['name' => 'Alt', 'slug' => 'alt']);

        $this->lists("DECKBAU\nDrafting\n", "Kooperativ\n");
        $this->seed(TaxonomySeeder::class);

        $this->assertSame('DECKBAU', $deckbau->fresh()?->name, 'spelling follows the list');
        $this->assertSame(2, Mechanic::count());
        $this->assertTrue($game->mechanics()->whereKey($deckbau->id)->exists());
        $this->assertTrue(Tag::where('slug', 'alt')->exists(), 'entries missing from the list are kept');
    }

    public function test_invalid_list_changes_nothing(): void
    {
        $this->lists("Deckbau\n", "Kooperativ\nkooperativ\n");

        try {
            $this->seed(TaxonomySeeder::class);
            $this->fail('expected the duplicate to be rejected');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('doppelt', $e->getMessage());
        }

        $this->assertSame(0, Mechanic::count());
    }
}

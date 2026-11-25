<?php

namespace Tests\Feature\Admin;

use App\Models\Game;
use App\Models\Mechanic;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Admins maintain tags and mechanics; their slugs drive the game filters. */
class TaxonomyAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /** @return list<array{string, class-string<Mechanic|Tag>}> */
    public static function kinds(): array
    {
        return [['mechanics', Mechanic::class], ['tags', Tag::class]];
    }

    /** @param  class-string<Mechanic|Tag>  $model */
    #[DataProvider('kinds')]
    public function test_create_rename_and_delete(string $path, string $model): void
    {
        $id = $this->actingAs($this->admin)
            ->postJson("/api/admin/{$path}", ['name' => 'Test Würfeln'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'test-wurfeln')
            ->json('data.id');

        $this->actingAs($this->admin)
            ->putJson("/api/admin/{$path}/{$id}", ['name' => 'Test Würfel'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'test-wurfel');

        $this->actingAs($this->admin)->deleteJson("/api/admin/{$path}/{$id}")->assertOk();
        $this->assertNull($model::find($id));
    }

    /** @param  class-string<Mechanic|Tag>  $model */
    #[DataProvider('kinds')]
    public function test_names_that_slug_alike_get_distinct_slugs_instead_of_an_error(string $path, string $model): void
    {
        $this->actingAs($this->admin)->postJson("/api/admin/{$path}", ['name' => 'Test-Deck Bau'])
            ->assertCreated()->assertJsonPath('data.slug', 'test-deck-bau');
        $this->actingAs($this->admin)->postJson("/api/admin/{$path}", ['name' => 'Test Deck-Bau'])
            ->assertCreated()->assertJsonPath('data.slug', 'test-deck-bau-2');

        // renaming to its own name keeps the slug instead of numbering itself
        $first = $model::where('slug', 'test-deck-bau')->firstOrFail();
        $this->actingAs($this->admin)->putJson("/api/admin/{$path}/{$first->id}", ['name' => 'Test-Deck Bau'])
            ->assertOk()->assertJsonPath('data.slug', 'test-deck-bau');
    }

    #[DataProvider('kinds')]
    public function test_duplicate_names_are_refused(string $path, string $model): void
    {
        $this->actingAs($this->admin)->postJson("/api/admin/{$path}", ['name' => 'Test Einmalig'])->assertCreated();
        $this->actingAs($this->admin)->postJson("/api/admin/{$path}", ['name' => 'Test Einmalig'])
            ->assertJsonValidationErrors('name');
    }

    public function test_index_counts_games_and_deleting_unlinks_them(): void
    {
        $mechanic = Mechanic::factory()->create(['name' => 'Test Zählen', 'slug' => 'test-zaehlen']);
        $game = Game::factory()->create();
        $game->mechanics()->attach($mechanic);

        $row = collect($this->actingAs($this->admin)->getJson('/api/admin/mechanics')->assertOk()->json('data'))
            ->firstWhere('slug', 'test-zaehlen');
        $this->assertSame(1, $row['games_count']);

        $this->actingAs($this->admin)->deleteJson("/api/admin/mechanics/{$mechanic->id}")->assertOk();
        $this->assertModelExists($game);
        $this->assertSame(0, $game->mechanics()->count());
    }

    #[DataProvider('kinds')]
    public function test_members_cannot_manage_them(string $path, string $model): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->postJson("/api/admin/{$path}", ['name' => 'X'])
            ->assertForbidden();
    }
}

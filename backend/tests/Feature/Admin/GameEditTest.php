<?php

namespace Tests\Feature\Admin;

use App\Models\Game;
use App\Models\Language;
use App\Models\Mechanic;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Saving the admin game form the way the frontend sends it (multipart). */
class GameEditTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string, mixed>  $fields */
    private function save(Game $game, array $fields): TestResponse
    {
        $body = ['title' => $game->title, 'slug' => $game->slug, 'size_category' => 'MEDIUM', 'is_active' => '1', ...$fields];

        return $this->actingAs(User::factory()->admin()->create())
            ->post("/api/admin/games/{$game->id}?_method=PUT", $body, ['Accept' => 'application/json']);
    }

    public function test_emptied_fields_are_cleared_and_an_empty_deposit_means_none(): void
    {
        $game = Game::factory()->create([
            'description' => 'Alt',
            'instagram_url' => 'https://instagram.com/p/x',
            'year' => 2015,
            'deposit_tokens' => 5,
        ]);

        $this->save($game, ['description' => '', 'instagram_url' => '', 'year' => '', 'deposit_tokens' => ''])->assertOk();

        $game->refresh();
        $this->assertNull($game->description);
        $this->assertNull($game->instagram_url);
        $this->assertNull($game->year);
        $this->assertSame(0, $game->deposit_tokens);
    }

    public function test_the_last_tag_mechanic_and_language_can_be_removed(): void
    {
        $game = Game::factory()->create();
        $game->tags()->attach(Tag::factory()->create());
        $game->mechanics()->attach(Mechanic::factory()->create());
        $game->languages()->attach(Language::factory()->create());

        $this->save($game, ['tag_ids' => '', 'mechanic_ids' => '', 'language_ids' => ''])->assertOk();

        $this->assertSame(0, $game->tags()->count());
        $this->assertSame(0, $game->mechanics()->count());
        $this->assertSame(0, $game->languages()->count());
    }

    public function test_lists_are_kept_when_not_sent_and_replaced_when_sent(): void
    {
        $game = Game::factory()->create();
        [$a, $b] = Tag::factory()->count(2)->create();
        $game->tags()->attach($a);

        $this->save($game, [])->assertOk();
        $this->assertSame([$a->id], $game->tags()->pluck('tags.id')->all());

        $this->save($game, ['tag_ids' => [$b->id]])->assertOk();
        $this->assertSame([$b->id], $game->tags()->pluck('tags.id')->all());
    }

    public function test_unchecked_fields_are_ignored(): void
    {
        $game = Game::factory()->create(['cover_image_url' => null]);

        $this->save($game, ['cover_image_url' => 'https://evil.example/x.png'])->assertOk();

        $this->assertNull($game->fresh()?->getRawOriginal('cover_image_url'));
    }
}

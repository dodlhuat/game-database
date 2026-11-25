<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Language;
use App\Models\Mechanic;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function titles(string $query): array
    {
        return collect($this->getJson('/api/games?'.$query)->assertOk()->json('data'))
            ->pluck('title')
            ->all();
    }

    public function test_mechanic_filter_matches_by_slug_and_combines_with_or(): void
    {
        $deck = Mechanic::factory()->create(['name' => 'Test-Deckbau', 'slug' => 'test-deckbau']);
        $worker = Mechanic::factory()->create(['name' => 'Test-Arbeiter', 'slug' => 'test-arbeiter']);
        $dice = Mechanic::factory()->create(['name' => 'Test-Würfel', 'slug' => 'test-wuerfel']);
        Game::factory()->create(['title' => 'Dominion'])->mechanics()->attach($deck);
        Game::factory()->create(['title' => 'Agricola'])->mechanics()->attach($worker);
        Game::factory()->create(['title' => 'Kniffel'])->mechanics()->attach($dice);

        $this->assertSame(['Dominion'], $this->titles('mechanic=test-deckbau'));
        $this->assertEqualsCanonicalizing(['Dominion', 'Agricola'], $this->titles('mechanic=test-deckbau,test-arbeiter'));
        $this->assertSame([], $this->titles('mechanic=unknown'));
    }

    public function test_mechanic_filter_does_not_take_ids(): void
    {
        // The list page once sent ids, which silently matched nothing
        $deck = Mechanic::factory()->create(['name' => 'Test-Deckbau', 'slug' => 'test-deckbau']);
        Game::factory()->create(['title' => 'Dominion'])->mechanics()->attach($deck);

        $this->assertSame([], $this->titles("mechanic={$deck->id}"));
    }

    public function test_tag_filter_matches_by_slug(): void
    {
        $family = Tag::factory()->create(['name' => 'Test-Familie', 'slug' => 'test-familie']);
        Game::factory()->create(['title' => 'Carcassonne'])->tags()->attach($family);
        Game::factory()->create(['title' => 'Twilight Imperium']);

        $this->assertSame(['Carcassonne'], $this->titles('tag=test-familie'));
    }

    public function test_players_filter_matches_games_playable_with_that_count(): void
    {
        Game::factory()->create(['title' => 'Small', 'min_players' => 2, 'max_players' => 4]);
        Game::factory()->create(['title' => 'Big', 'min_players' => 5, 'max_players' => 8]);

        $this->assertSame(['Small'], $this->titles('players=3'));
        $this->assertSame(['Big'], $this->titles('players=6'));
    }

    public function test_players_filter_is_ignored_for_empty_zero_or_non_numeric_values(): void
    {
        Game::factory()->create(['title' => 'Small', 'min_players' => 2, 'max_players' => 4]);
        Game::factory()->create(['title' => 'Big', 'min_players' => 5, 'max_players' => 8]);

        foreach (['players=', 'players=0', 'players=abc'] as $query) {
            $this->assertEqualsCanonicalizing(['Small', 'Big'], $this->titles($query), $query);
        }
    }

    public function test_language_filter_matches_by_id_and_ignores_non_numeric_values(): void
    {
        $german = Language::factory()->create();
        $english = Language::factory()->create();
        Game::factory()->create(['title' => 'Deutsch'])->languages()->attach($german);
        Game::factory()->create(['title' => 'English'])->languages()->attach($english);

        $this->assertSame(['Deutsch'], $this->titles('language='.$german->id));
        $this->assertEqualsCanonicalizing(['Deutsch', 'English'], $this->titles('language=abc'));
    }

    public function test_available_filter_only_applies_when_truthy(): void
    {
        $free = Game::factory()->create(['title' => 'Free']);
        Copy::factory()->create(['game_id' => $free->id]);
        $locked = Game::factory()->create(['title' => 'Locked']);
        Copy::factory()->locked()->create(['game_id' => $locked->id]);

        $this->assertSame(['Free'], $this->titles('available=1'));
        $this->assertSame(['Free'], $this->titles('available=true'));
        $this->assertEqualsCanonicalizing(['Free', 'Locked'], $this->titles('available=false'));
        $this->assertEqualsCanonicalizing(['Free', 'Locked'], $this->titles('available=0'));
    }

    public function test_min_age_range_filter(): void
    {
        Game::factory()->create(['title' => 'Kids', 'min_age' => 6]);
        Game::factory()->create(['title' => 'Teens', 'min_age' => 12]);

        $this->assertSame(['Kids'], $this->titles('min_age_to=8'));
        $this->assertSame(['Teens'], $this->titles('min_age_from=10'));
        $this->assertSame(['Kids'], $this->titles('min_age_from=5&min_age_to=8'));
    }

    public function test_search_filters_by_title_and_description(): void
    {
        Game::factory()->create(['title' => 'Catan', 'description' => 'Handel']);
        Game::factory()->create(['title' => 'Azul', 'description' => 'Fliesen legen']);

        $this->assertSame(['Catan'], $this->titles('search=cat'));
        $this->assertSame(['Azul'], $this->titles('search=fliesen'));
        $this->assertEqualsCanonicalizing(['Catan', 'Azul'], $this->titles('search='));
    }

    public function test_difficulty_and_duration_filters(): void
    {
        Game::factory()->create(['title' => 'Quick', 'difficulty' => 'EASY', 'duration_min' => 20]);
        Game::factory()->create(['title' => 'Epic', 'difficulty' => 'HARD', 'duration_min' => 120]);

        $this->assertSame(['Epic'], $this->titles('difficulty=HARD'));
        $this->assertSame(['Quick'], $this->titles('duration=short'));
        $this->assertSame(['Epic'], $this->titles('duration=long'));
    }

    public function test_newest_sort_lists_the_latest_games_first(): void
    {
        Game::factory()->create(['title' => 'Old', 'created_at' => now()->subMonth()]);
        Game::factory()->create(['title' => 'New', 'created_at' => now()]);

        $this->assertSame(['New', 'Old'], $this->titles('sort=created_at'));
    }

    public function test_smart_search_applies_the_list_filters_too(): void
    {
        $deck = Mechanic::factory()->create(['name' => 'Test-Deckbau', 'slug' => 'test-deckbau']);
        Game::factory()->create(['title' => 'Abenteuer Deckbau', 'difficulty' => 'EASY'])->mechanics()->attach($deck);
        Game::factory()->create(['title' => 'Abenteuer Würfel', 'difficulty' => 'EASY']);
        Game::factory()->create(['title' => 'Abenteuer Schwer', 'difficulty' => 'HARD'])->mechanics()->attach($deck);

        $search = fn (string $query) => collect($this->getJson('/api/games/smart-search?q=Abenteuer&'.$query)->assertOk()->json('data'))
            ->pluck('title')->all();

        $this->assertEqualsCanonicalizing(['Abenteuer Deckbau', 'Abenteuer Würfel', 'Abenteuer Schwer'], $search(''));
        $this->assertEqualsCanonicalizing(['Abenteuer Deckbau', 'Abenteuer Schwer'], $search('mechanic=test-deckbau'));
        $this->assertSame(['Abenteuer Deckbau'], $search('mechanic=test-deckbau&difficulty=EASY'));
    }

    public function test_sort_orders_by_allowed_column_and_falls_back_to_title(): void
    {
        Game::factory()->create(['title' => 'B Game', 'min_age' => 6]);
        Game::factory()->create(['title' => 'A Game', 'min_age' => 12]);

        $this->assertSame(['B Game', 'A Game'], $this->titles('sort=min_age'));
        $this->assertSame(['A Game', 'B Game'], $this->titles('sort=title'));
        $this->assertSame(['A Game', 'B Game'], $this->titles('sort=password'));
        $this->assertSame(['A Game', 'B Game'], $this->titles(''));
    }
}

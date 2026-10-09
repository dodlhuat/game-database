<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Language;
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

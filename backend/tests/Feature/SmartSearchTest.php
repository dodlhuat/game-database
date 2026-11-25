<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Mechanic;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** The search field: "Spiele wie …", mechanics, tags and plain text. */
class SmartSearchTest extends TestCase
{
    use RefreshDatabase;

    private function search(string $q): TestResponse
    {
        return $this->getJson('/api/games/smart-search?q='.urlencode($q))->assertOk();
    }

    /** @return list<string> */
    private function titles(TestResponse $response): array
    {
        return $response->json('data.*.title');
    }

    private function game(string $title, array $attrs = []): Game
    {
        return Game::factory()->create(['title' => $title, 'is_active' => true, 'min_players' => 2, 'max_players' => 4, 'duration_min' => 45, 'min_age' => 8, ...$attrs]);
    }

    public function test_similar_games_rank_by_shared_mechanics_tags_and_fit(): void
    {
        $drafting = Mechanic::factory()->create(['name' => 'Testdraft', 'slug' => 'testdraft']);
        $pattern = Mechanic::factory()->create(['name' => 'Testmuster', 'slug' => 'testmuster']);
        $family = Tag::factory()->create(['name' => 'Testfamilie', 'slug' => 'testfamilie']);

        $azul = $this->game('Azul');
        $azul->mechanics()->attach([$drafting->id, $pattern->id]);
        $azul->tags()->attach($family);

        $close = $this->game('Sagrada');                 // both mechanics + tag
        $close->mechanics()->attach([$drafting->id, $pattern->id]);
        $close->tags()->attach($family);
        $some = $this->game('Kingdomino');               // one mechanic
        $some->mechanics()->attach($drafting);
        // nothing in common: different player count, length and age
        $this->game('Twilight Imperium', ['min_players' => 6, 'max_players' => 8, 'duration_min' => 480, 'min_age' => 16]);

        $response = $this->search('Spiele wie Azul')
            ->assertJsonPath('meta.intent', 'SIMILARITY')
            ->assertJsonPath('meta.reference_title', 'Azul');

        $this->assertSame(['Sagrada', 'Kingdomino'], $this->titles($response));
    }

    public function test_a_typo_in_the_reference_still_finds_it(): void
    {
        $this->game('Azul');
        $this->game('Sagrada');

        $this->search('ähnliche spiele wie azull')->assertJsonPath('meta.reference_title', 'Azul');
    }

    public function test_an_unknown_reference_falls_back_to_a_title_search(): void
    {
        $this->game('Azul');

        $this->search('Spiele wie Monopoly')
            ->assertJsonPath('meta.intent', 'FULLTEXT')
            ->assertJsonPath('data', []);
    }

    public function test_mechanic_and_tag_names_filter_by_them(): void
    {
        $mechanic = Mechanic::factory()->create(['name' => 'Testarbeiter', 'slug' => 'testarbeiter']);
        $tag = Tag::factory()->create(['name' => 'Testkooperativ', 'slug' => 'testkooperativ']);
        $this->game('Agricola')->mechanics()->attach($mechanic);
        $this->game('Pandemie')->tags()->attach($tag);
        $this->game('Azul');

        $this->assertSame(['Agricola'], $this->titles($this->search('testarbeiter')->assertJsonPath('meta.intent', 'MECHANIC')));
        $this->assertSame(['Pandemie'], $this->titles($this->search('testkooperativ')->assertJsonPath('meta.intent', 'TAG')));
    }

    public function test_plain_text_searches_title_and_description_ignoring_case(): void
    {
        $this->game('Azul', ['description' => 'Fliesen legen']);
        $this->game('Carcassonne', ['description' => 'Landschaften aus Plättchen']);
        $this->game('Inaktiv mit Fliesen', ['is_active' => false]);

        $this->assertSame(['Azul'], $this->titles($this->search('FLIESEN')));
        $this->search('')->assertJsonPath('meta.intent', 'EMPTY');
    }
}

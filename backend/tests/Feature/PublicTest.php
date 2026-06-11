<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CookieVersion;
use App\Models\Game;
use App\Models\Language;
use App\Models\LoanSetting;
use App\Models\Package;
use App\Models\PrivacyVersion;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_games_index_returns_paginated_list(): void
    {
        Game::factory()->count(3)->create();

        $this->getJson('/api/games')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_games_index_excludes_inactive(): void
    {
        Game::factory()->inactive()->create(['title' => 'Hidden Game']);
        Game::factory()->create(['title' => 'Visible Game']);

        $response = $this->getJson('/api/games')->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Visible Game', $titles->all());
        $this->assertNotContains('Hidden Game', $titles->all());
    }

    public function test_game_show_returns_game_by_slug(): void
    {
        $game = Game::factory()->create(['slug' => 'my-game']);

        $this->getJson('/api/games/my-game')
            ->assertOk()
            ->assertJsonPath('data.slug', 'my-game');
    }

    public function test_game_show_returns_404_for_inactive(): void
    {
        Game::factory()->inactive()->create(['slug' => 'inactive-game']);

        $this->getJson('/api/games/inactive-game')->assertNotFound();
    }

    public function test_categories_index_returns_list(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_packages_index_returns_list(): void
    {
        Package::factory()->count(2)->create();

        $this->getJson('/api/packages')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_package_show_by_slug(): void
    {
        Package::factory()->create(['slug' => 'starter-pack']);

        $this->getJson('/api/packages/starter-pack')
            ->assertOk()
            ->assertJsonPath('data.slug', 'starter-pack');
    }

    public function test_loan_settings_returns_settings(): void
    {
        LoanSetting::factory()->create();

        $this->getJson('/api/loan-settings')->assertOk();
    }

    public function test_languages_index_returns_list(): void
    {
        Language::factory()->count(2)->create();

        $this->getJson('/api/languages')
            ->assertOk()
            ->assertJsonStructure(['*' => ['id', 'name']]);
    }

    public function test_terms_returns_latest_version(): void
    {
        TermsVersion::create([
            'version'      => '1.0',
            'content'      => 'Terms content',
            'published_at' => now(),
        ]);

        $this->getJson('/api/terms')
            ->assertOk()
            ->assertJsonStructure(['version', 'content']);
    }

    public function test_privacy_returns_latest_version(): void
    {
        PrivacyVersion::create([
            'version'      => '1.0',
            'content'      => 'Privacy content',
            'published_at' => now(),
        ]);

        $this->getJson('/api/privacy')
            ->assertOk()
            ->assertJsonStructure(['version', 'content']);
    }

    public function test_cookies_returns_latest_version(): void
    {
        CookieVersion::create([
            'version'      => '1.0',
            'content'      => 'Cookie content',
            'published_at' => now(),
        ]);

        $this->getJson('/api/cookies')
            ->assertOk()
            ->assertJsonStructure(['version', 'content']);
    }

    public function test_events_requires_auth(): void
    {
        $this->getJson('/api/events')->assertUnauthorized();
    }

    public function test_events_returns_list_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        \App\Models\Event::factory()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/events')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}

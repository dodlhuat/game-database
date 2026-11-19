<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class AddressValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Cache::flush();
    }

    private function validate(string $street, string $postalCode = '3100', string $city = 'St. Pölten')
    {
        return $this->actingAs(User::factory()->create())
            ->postJson('/api/address/validate', ['street' => $street, 'postal_code' => $postalCode, 'city' => $city]);
    }

    public function test_stair_and_door_are_stripped_before_the_lookup(): void
    {
        Http::fake(['*' => Http::response([['display_name' => 'x']])]);

        $this->validate('Kremser Gasse 41/2/7')
            ->assertOk()
            ->assertJson(['verified' => true, 'status' => 'verified']);

        Http::assertSent(fn (Request $r) => $r['street'] === 'Kremser Gasse 41' && $r['city'] === 'St. Pölten');
    }

    public function test_retries_without_city_when_the_first_lookup_finds_nothing(): void
    {
        Http::fakeSequence()
            ->push([])
            ->push([['display_name' => 'x']]);

        $this->validate('Kremser Gasse 41', '3100', 'Spratzern')
            ->assertOk()
            ->assertJson(['verified' => true, 'status' => 'verified']);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => ! isset($r['city']) && $r['postalcode'] === '3100');
        Sleep::assertSleptTimes(1);
    }

    public function test_reports_not_found_when_both_lookups_miss(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->validate('Gibtsnichtgasse 99')
            ->assertOk()
            ->assertJson(['verified' => false, 'status' => 'not_found']);
    }

    public function test_reports_unavailable_and_does_not_cache_when_nominatim_fails(): void
    {
        Http::fakeSequence()
            ->push('rate limited', 429)
            ->push([['display_name' => 'x']]);

        $this->validate('Kremser Gasse 41')
            ->assertOk()
            ->assertJson(['verified' => false, 'status' => 'unavailable']);

        // The failure was not cached, so the next attempt asks Nominatim again.
        $this->validate('Kremser Gasse 41')->assertJson(['status' => 'verified']);
    }

    public function test_definitive_results_are_cached_per_building(): void
    {
        Http::fake(['*' => Http::response([['display_name' => 'x']])]);

        $this->validate('Kremser Gasse 41/2/7')->assertJson(['status' => 'verified']);
        $this->validate('Kremser Gasse 41 Top 3')->assertJson(['status' => 'verified']);

        Http::assertSentCount(1);
    }
}

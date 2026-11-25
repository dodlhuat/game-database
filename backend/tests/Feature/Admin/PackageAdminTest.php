<?php

namespace Tests\Feature\Admin;

use App\Models\Game;
use App\Models\Loan;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admins put games together into packages, edit and retire them. */
class PackageAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_create_with_games_and_a_slug_from_the_name(): void
    {
        $games = Game::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->postJson('/api/admin/packages', [
                'name' => 'Familien Abend',
                'type' => 'CURATED',
                'game_ids' => $games->pluck('id')->all(),
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'familien-abend')
            ->assertJsonPath('data.games_count', 2);
    }

    public function test_same_name_twice_gets_a_numbered_slug_instead_of_an_error(): void
    {
        foreach (['familie', 'familie-2', 'familie-3'] as $expected) {
            $this->actingAs($this->admin)
                ->postJson('/api/admin/packages', ['name' => 'Familie', 'type' => 'CATEGORY'])
                ->assertCreated()
                ->assertJsonPath('data.slug', $expected);
        }
    }

    public function test_a_chosen_slug_must_be_unique(): void
    {
        Package::factory()->create(['slug' => 'familie']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/packages', ['name' => 'Neu', 'slug' => 'familie', 'type' => 'CATEGORY'])
            ->assertJsonValidationErrors('slug');
    }

    public function test_update_replaces_the_games_only_when_sent(): void
    {
        $package = Package::factory()->create(['name' => 'Alt']);
        [$a, $b, $c] = Game::factory()->count(3)->create();
        $package->games()->attach([$a->id, $b->id]);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/packages/{$package->id}", ['name' => 'Neu'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Neu')
            ->assertJsonPath('data.games_count', 2);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/packages/{$package->id}", ['game_ids' => [$c->id]])
            ->assertOk()
            ->assertJsonPath('data.games_count', 1);
        $this->assertSame([$c->id], $package->games()->pluck('games.id')->all());
    }

    public function test_index_and_show_list_the_games(): void
    {
        $package = Package::factory()->create(['name' => 'Strategie']);
        $package->games()->attach(Game::factory()->create(['title' => 'Azul']));

        $this->actingAs($this->admin)->getJson('/api/admin/packages')
            ->assertOk()
            ->assertJsonPath('data.0.games.0.title', 'Azul');
        $this->actingAs($this->admin)->getJson("/api/admin/packages/{$package->id}")
            ->assertOk()
            ->assertJsonPath('data.games.0.title', 'Azul');
    }

    public function test_unborrowed_package_can_be_deleted(): void
    {
        $package = Package::factory()->create();

        $this->actingAs($this->admin)->deleteJson("/api/admin/packages/{$package->id}")->assertOk();
        $this->assertModelMissing($package);
    }

    public function test_borrowed_package_cannot_be_deleted_so_its_loans_survive(): void
    {
        $package = Package::factory()->create();
        $packageLoan = PackageLoan::factory()->create(['package_id' => $package->id]);
        $loan = Loan::factory()->create(['package_loan_id' => $packageLoan->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/packages/{$package->id}")
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'has_loans');

        $this->assertModelExists($packageLoan);
        $this->assertSame($packageLoan->id, $loan->fresh()?->package_loan_id);
    }

    public function test_members_cannot_manage_packages(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->getJson('/api/admin/packages')->assertForbidden();
        $this->actingAs($member)->postJson('/api/admin/packages', ['name' => 'X', 'type' => 'CATEGORY'])->assertForbidden();
    }
}

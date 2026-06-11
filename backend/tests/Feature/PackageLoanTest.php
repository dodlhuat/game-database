<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Game;
use App\Models\LoanSetting;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageLoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/package-loans')->assertUnauthorized();
    }

    public function test_index_returns_user_package_loans(): void
    {
        $user = User::factory()->member()->create();
        PackageLoan::factory()->count(2)->create(['user_id' => $user->id]);
        PackageLoan::factory()->create(); // other user

        $this->actingAs($user)
            ->getJson('/api/package-loans')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_creates_package_loan_for_admin(): void
    {
        LoanSetting::factory()->create();
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create(['deposit_tokens' => 0]);
        $copy = Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
        $package = Package::factory()->create(['is_active' => true]);
        $package->games()->attach($game->id);

        $this->actingAs($admin)
            ->postJson('/api/package-loans', ['package_id' => $package->id])
            ->assertCreated();
    }

    public function test_store_fails_for_non_member(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->create(['role' => 'USER', 'status' => 'ACTIVE']);
        $game = Game::factory()->create(['deposit_tokens' => 0]);
        Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
        $package = Package::factory()->create(['is_active' => true]);
        $package->games()->attach($game->id);

        $this->actingAs($user)
            ->postJson('/api/package-loans', ['package_id' => $package->id])
            ->assertStatus(403)
            ->assertJsonPath('reason', 'membership_required');
    }

    public function test_store_fails_for_inactive_package(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 50]);
        $package = Package::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->postJson('/api/package-loans', ['package_id' => $package->id])
            ->assertStatus(422);
    }

    public function test_return_marks_package_loan_returned(): void
    {
        $user = User::factory()->member()->create();
        $packageLoan = PackageLoan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->actingAs($user)
            ->postJson("/api/package-loans/{$packageLoan->id}/return")
            ->assertOk()
            ->assertJsonPath('data.status', 'RETURNED');
    }

    public function test_return_fails_for_already_returned(): void
    {
        $user = User::factory()->member()->create();
        $packageLoan = PackageLoan::factory()->create(['user_id' => $user->id, 'status' => 'RETURNED']);

        $this->actingAs($user)
            ->postJson("/api/package-loans/{$packageLoan->id}/return")
            ->assertStatus(422);
    }
}

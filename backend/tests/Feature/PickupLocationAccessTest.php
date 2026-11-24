<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** A location with assigned users is theirs alone; without any it stays open for everyone. */
class PickupLocationAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PickupLocation $open;

    private PickupLocation $restricted;

    private User $insider;

    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->open = PickupLocation::where('name', 'Hauptstandort')->firstOrFail();
        $this->restricted = PickupLocation::factory()->create(['name' => 'Vereinslokal']);
        $this->insider = User::factory()->member()->create(['tokens' => 20]);
        $this->outsider = User::factory()->member()->create(['tokens' => 20]);
        $this->restricted->users()->attach($this->insider);
    }

    private function borrowableCopy(): Copy
    {
        LoanSetting::factory()->create(['deposit_pct_very_good' => 0]);

        return Copy::factory()->create(['condition' => 'NEW']);
    }

    /** @return list<string> */
    private function visibleNames(User $user): array
    {
        return collect($this->actingAs($user)->getJson('/api/pickup-locations')->assertOk()->json('data'))
            ->pluck('name')->all();
    }

    public function test_restricted_location_is_only_listed_for_assigned_users_and_admins(): void
    {
        $this->assertSame(['Hauptstandort', 'Vereinslokal'], $this->visibleNames($this->insider));
        $this->assertSame(['Hauptstandort'], $this->visibleNames($this->outsider));
        $this->assertSame(['Hauptstandort', 'Vereinslokal'], $this->visibleNames($this->admin));
    }

    public function test_member_list_never_exposes_who_a_location_is_restricted_to(): void
    {
        $this->actingAs($this->insider)
            ->getJson('/api/pickup-locations')
            ->assertOk()
            ->assertJsonMissingPath('data.1.users');
    }

    public function test_outsider_cannot_borrow_at_a_restricted_location(): void
    {
        $copy = $this->borrowableCopy();

        $this->actingAs($this->outsider)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_location_id' => $this->restricted->id])
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'invalid_pickup_location');

        $this->assertSame(0, Loan::count());
        $this->assertSame(20, $this->outsider->fresh()?->tokens);
    }

    public function test_assigned_user_can_borrow_at_the_restricted_location(): void
    {
        $copy = $this->borrowableCopy();

        $this->actingAs($this->insider)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_location_id' => $this->restricted->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_location.id', $this->restricted->id);
    }

    public function test_outsider_cannot_borrow_a_package_at_a_restricted_location(): void
    {
        LoanSetting::factory()->create();
        $package = Package::factory()->create(['is_active' => true]);
        $game = Game::factory()->create(['deposit_tokens' => 0]);
        Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
        $package->games()->attach($game->id);

        $this->actingAs($this->outsider)
            ->postJson('/api/package-loans', ['package_id' => $package->id, 'pickup_location_id' => $this->restricted->id])
            ->assertUnprocessable();

        $this->assertSame(0, PackageLoan::count());
    }

    public function test_the_only_location_open_to_a_user_is_implied(): void
    {
        // Two active locations exist, but the outsider can only use one of them
        $copy = $this->borrowableCopy();

        $this->actingAs($this->outsider)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_location.id', $this->open->id);
    }

    public function test_running_loans_stay_untouched_when_access_is_withdrawn(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->insider->id,
            'pickup_location_id' => $this->restricted->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->restricted->id}", [
                'name' => 'Vereinslokal',
                'user_ids' => [$this->outsider->id],
            ])
            ->assertOk();

        $this->assertSame($this->restricted->id, $loan->fresh()?->pickup_location_id);
        $this->actingAs($this->insider)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])
            ->assertOk();
    }

    public function test_admin_syncs_users_and_an_empty_list_opens_the_location_again(): void
    {
        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->restricted->id}", [
                'name' => 'Vereinslokal',
                'user_ids' => [$this->insider->id, $this->outsider->id],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data.users')
            ->assertJsonPath('data.users.0.can_borrow', true);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->restricted->id}", ['name' => 'Vereinslokal', 'user_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.users');

        $third = User::factory()->member()->create();
        $this->assertContains('Vereinslokal', $this->visibleNames($third));
    }

    public function test_update_without_user_ids_keeps_the_assignment(): void
    {
        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->restricted->id}", ['name' => 'Vereinslokal neu'])
            ->assertOk()
            ->assertJsonCount(1, 'data.users');
    }

    public function test_new_location_can_be_created_restricted(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/pickup-locations', ['name' => 'Spieleabend', 'user_ids' => [$this->outsider->id]])
            ->assertCreated()
            ->assertJsonPath('data.users.0.id', $this->outsider->id);
    }

    public function test_admin_cannot_move_a_loan_to_a_location_closed_to_the_borrower(): void
    {
        $loan = Loan::factory()->create(['user_id' => $this->outsider->id, 'pickup_location_id' => $this->open->id]);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/loans/{$loan->id}/pickup-location", ['pickup_location_id' => $this->restricted->id])
            ->assertUnprocessable();

        $packageLoan = PackageLoan::factory()->create(['user_id' => $this->outsider->id, 'pickup_location_id' => $this->open->id]);
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/package-loans/{$packageLoan->id}/pickup-location", ['pickup_location_id' => $this->restricted->id])
            ->assertUnprocessable();

        $insiderLoan = Loan::factory()->create(['user_id' => $this->insider->id, 'pickup_location_id' => $this->open->id]);
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/loans/{$insiderLoan->id}/pickup-location", ['pickup_location_id' => $this->restricted->id])
            ->assertOk();
    }

    public function test_admin_sees_which_locations_a_user_may_use(): void
    {
        $names = fn (User $u) => collect($this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$u->id}/pickup-locations")->assertOk()->json('data'))->pluck('name')->all();

        $this->assertSame(['Hauptstandort', 'Vereinslokal'], $names($this->insider));
        $this->assertSame(['Hauptstandort'], $names($this->outsider));
    }
}

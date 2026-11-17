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
use App\Notifications\LoanConfirmation;
use App\Notifications\LoanDueSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PickupLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PickupLocation $main;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        // Created by the migration, like in production.
        $this->main = PickupLocation::where('name', 'Hauptstandort')->firstOrFail();
    }

    private function borrowableCopy(): Copy
    {
        LoanSetting::factory()->create(['deposit_pct_very_good' => 0]);

        return Copy::factory()->create(['condition' => 'NEW']);
    }

    // ── Public list ──────────────────────────────────────────────

    public function test_migration_creates_one_active_default_location(): void
    {
        $this->assertSame(1, PickupLocation::count());
        $this->assertTrue($this->main->is_active);
    }

    public function test_public_list_requires_login_and_shows_only_active_in_order(): void
    {
        PickupLocation::factory()->create(['name' => 'Zweiter', 'sort_order' => 0]);
        PickupLocation::factory()->inactive()->create(['name' => 'Inaktiv']);
        PickupLocation::factory()->create(['name' => 'Gelöscht'])->delete();

        $this->getJson('/api/pickup-locations')->assertUnauthorized();

        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/pickup-locations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Zweiter')
            ->assertJsonPath('data.1.name', 'Hauptstandort');
    }

    // ── Admin CRUD ───────────────────────────────────────────────

    public function test_admin_endpoints_require_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/pickup-locations')
            ->assertForbidden();
    }

    public function test_name_is_the_only_required_field_and_map_url_must_be_http(): void
    {
        $this->actingAs($this->admin)->postJson('/api/admin/pickup-locations', [])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->actingAs($this->admin)->postJson('/api/admin/pickup-locations', [
            'name' => 'Café', 'map_url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors('map_url');

        $this->actingAs($this->admin)->postJson('/api/admin/pickup-locations', [
            'name' => 'Café',
            'address' => "Hauptplatz 1\n1010 Wien",
            'opening_hours' => 'Mo–Fr 9–18',
            'notes' => '  ',
            'map_url' => 'https://maps.example/cafe',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Café')
            ->assertJsonPath('data.notes', null)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.sort_order', 2);
    }

    public function test_rename_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->main->id}", ['name' => 'Vereinslokal'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Vereinslokal')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_last_active_location_cannot_be_deactivated_or_deleted(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/pickup-locations/{$this->main->id}/toggle")
            ->assertUnprocessable()->assertJsonPath('reason', 'last_active_location');

        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->main->id}", ['name' => 'X', 'is_active' => false])
            ->assertUnprocessable()->assertJsonPath('reason', 'last_active_location');

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/pickup-locations/{$this->main->id}")
            ->assertUnprocessable()->assertJsonPath('reason', 'last_active_location');

        $this->assertTrue($this->main->fresh()?->is_active);
    }

    public function test_location_with_open_loans_cannot_be_deactivated_or_deleted(): void
    {
        PickupLocation::factory()->create();
        Loan::factory()->create(['pickup_location_id' => $this->main->id, 'status' => 'EXTENDED']);
        Loan::factory()->create(['pickup_location_id' => $this->main->id, 'status' => 'RETURNED']);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/pickup-locations/{$this->main->id}/toggle")
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'open_loans')
            ->assertJsonPath('open_loans_count', 1);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/pickup-locations/{$this->main->id}")
            ->assertUnprocessable()->assertJsonPath('reason', 'open_loans');

        $this->actingAs($this->admin)
            ->getJson('/api/admin/pickup-locations')
            ->assertJsonPath('data.0.open_loans_count', 1);
    }

    public function test_toggle_and_soft_delete_when_allowed(): void
    {
        $other = PickupLocation::factory()->create();
        Loan::factory()->create(['pickup_location_id' => $other->id, 'status' => 'RETURNED']);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/pickup-locations/{$other->id}/toggle")
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/pickup-locations/{$other->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($other);
        $this->assertNotNull(Loan::first()?->pickupLocation, 'past loans still resolve their location');
    }

    public function test_reorder_sets_positions(): void
    {
        $other = PickupLocation::factory()->create();

        $this->actingAs($this->admin)
            ->putJson('/api/admin/pickup-locations/order', ['ids' => [$other->id, $this->main->id]])
            ->assertNoContent();

        $this->assertSame(1, $other->fresh()?->sort_order);
        $this->assertSame(2, $this->main->fresh()?->sort_order);
    }

    // ── Borrowing ────────────────────────────────────────────────

    public function test_loan_stores_chosen_location_and_sends_confirmation(): void
    {
        Notification::fake();
        $copy = $this->borrowableCopy();
        $cafe = PickupLocation::factory()->create(['name' => 'Café']);
        $user = User::factory()->member()->create(['tokens' => 10]);

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_location_id' => $cafe->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_location.name', 'Café');

        $this->assertSame($cafe->id, Loan::first()?->pickup_location_id);
        Notification::assertSentTo($user, LoanConfirmation::class);
    }

    public function test_location_is_implied_while_only_one_is_active(): void
    {
        Notification::fake();
        $copy = $this->borrowableCopy();
        PickupLocation::factory()->inactive()->create();

        $this->actingAs(User::factory()->member()->create(['tokens' => 10]))
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_location.id', $this->main->id);
    }

    public function test_choice_is_required_with_several_active_and_must_be_active(): void
    {
        $copy = $this->borrowableCopy();
        $inactive = PickupLocation::factory()->inactive()->create();
        PickupLocation::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 10]);

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertUnprocessable()->assertJsonPath('reason', 'invalid_pickup_location');

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_location_id' => $inactive->id])
            ->assertUnprocessable()->assertJsonPath('reason', 'invalid_pickup_location');

        $this->assertSame(0, Loan::count());
        $this->assertSame(10, $user->fresh()?->tokens, 'nothing was charged');
    }

    public function test_package_loan_stores_location_on_package_and_each_game(): void
    {
        Notification::fake();
        LoanSetting::factory()->create();
        $cafe = PickupLocation::factory()->create();
        $package = Package::factory()->create(['is_active' => true]);
        foreach (range(1, 2) as $_) {
            $game = Game::factory()->create(['deposit_tokens' => 0]);
            Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
            $package->games()->attach($game->id);
        }

        $this->actingAs($this->admin)
            ->postJson('/api/package-loans', ['package_id' => $package->id, 'pickup_location_id' => $cafe->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_location.id', $cafe->id);

        $this->assertSame($cafe->id, PackageLoan::first()?->pickup_location_id);
        $this->assertSame(2, Loan::where('pickup_location_id', $cafe->id)->count());
        Notification::assertSentTo($this->admin, LoanConfirmation::class);
    }

    // ── Admin: move a running loan ───────────────────────────────

    public function test_admin_can_move_a_running_loan_to_another_active_location(): void
    {
        $cafe = PickupLocation::factory()->create();
        $closed = PickupLocation::factory()->inactive()->create();
        $loan = Loan::factory()->create(['pickup_location_id' => $this->main->id, 'status' => 'ACTIVE']);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/loans/{$loan->id}/pickup-location", ['pickup_location_id' => $closed->id])
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/loans/{$loan->id}/pickup-location", ['pickup_location_id' => $cafe->id])
            ->assertOk()
            ->assertJsonPath('data.pickup_location.id', $cafe->id);
    }

    public function test_package_games_move_together_with_the_package(): void
    {
        $cafe = PickupLocation::factory()->create();
        $packageLoan = PackageLoan::factory()->create(['pickup_location_id' => $this->main->id, 'status' => 'ACTIVE']);
        $loan = Loan::factory()->create(['package_loan_id' => $packageLoan->id, 'pickup_location_id' => $this->main->id]);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/loans/{$loan->id}/pickup-location", ['pickup_location_id' => $cafe->id])
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/package-loans/{$packageLoan->id}/pickup-location", ['pickup_location_id' => $cafe->id])
            ->assertOk();

        $this->assertSame($cafe->id, $loan->fresh()?->pickup_location_id);
    }

    // ── Mails ────────────────────────────────────────────────────

    public function test_mails_name_the_return_location_with_details(): void
    {
        $this->main->update([
            'address' => 'Hauptplatz 1',
            'opening_hours' => 'Di 18–20 Uhr',
            'map_url' => 'https://maps.example/x?a=1&b=2',
        ]);
        $user = User::factory()->member()->create();
        $loan = Loan::factory()->create(['user_id' => $user->id, 'pickup_location_id' => $this->main->id]);

        $html = (string) (new LoanConfirmation($loan->fresh() ?? $loan))->toMail($user)->render();
        $this->assertStringContainsString('Hauptstandort', $html);
        $this->assertStringContainsString('Hauptplatz 1', $html);
        $this->assertStringContainsString('Di 18–20 Uhr', $html);
        $this->assertStringContainsString('https://maps.example/x?a=1&amp;b=2', $html);

        $html = (string) (new LoanDueSoon($loan->fresh() ?? $loan))->toMail($user)->render();
        $this->assertStringContainsString('Hauptplatz 1', $html);
    }

    public function test_due_soon_command_uses_the_editable_template(): void
    {
        Notification::fake();
        $loan = Loan::factory()->create([
            'status' => 'ACTIVE',
            'due_date' => Carbon::today()->addDays(2),
            'pickup_location_id' => $this->main->id,
        ]);

        $this->artisan('loans:send-due-soon-reminders')->assertSuccessful();

        /** @var User $user */
        $user = $loan->user;
        Notification::assertSentTo($user, LoanDueSoon::class);
    }
}

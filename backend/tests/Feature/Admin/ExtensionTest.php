<?php

namespace Tests\Feature\Admin;

use App\Models\Extension;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\User;
use App\Services\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExtensionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_returns_extensions(): void
    {
        Extension::factory()->count(2)->create(['status' => 'PENDING']);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/extensions')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/extensions')
            ->assertForbidden();
    }

    public function test_approve_sets_approved_status(): void
    {
        $loan = Loan::factory()->create(['status' => 'ACTIVE']);
        $extension = Extension::factory()->create([
            'loan_id' => $loan->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'EXTENDED']);
    }

    public function test_approve_fails_when_already_processed(): void
    {
        $extension = Extension::factory()->approved()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/approve")
            ->assertStatus(422);
    }

    public function test_reject_sets_rejected_status(): void
    {
        $extension = Extension::factory()->create(['status' => 'PENDING']);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'REJECTED');
    }

    public function test_reject_fails_when_already_processed(): void
    {
        $extension = Extension::factory()->rejected()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/reject")
            ->assertStatus(422);
    }

    public function test_index_shows_member_game_cost_and_reservation(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['name' => 'Mia', 'tokens' => 5]);
        $extension = $this->secondExtension($user);
        Reservation::factory()->create(['game_id' => $extension->loan->copy->game_id]);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/extensions')
            ->assertOk()
            ->assertJsonPath('data.0.loan.user.name', 'Mia')
            ->assertJsonPath('data.0.loan.copy.game.title', $extension->loan->copy->game->title)
            ->assertJsonPath('data.0.next_cost', 2)
            ->assertJsonPath('data.0.game_reserved', true);
    }

    public function test_approving_first_extension_is_free(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 0]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        $extension = Extension::factory()->create(['loan_id' => $loan->id, 'status' => 'PENDING']);

        $this->approve($extension)
            ->assertOk()
            ->assertJsonPath('data.tokens_charged', 0);

        $this->assertDatabaseMissing('token_transactions', ['type' => 'EXTENSION']);
    }

    public function test_approving_second_extension_spends_bonus_tokens_first(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 0]);
        app(TokenWallet::class)->grantBonus($user, 5, 'MEMBERSHIP', now()->addYear());
        $extension = $this->secondExtension($user);

        $this->approve($extension)
            ->assertOk()
            ->assertJsonPath('data.tokens_charged', 2);

        $this->assertSame(3, $user->fresh()->bonus_tokens);
        $this->assertSame(0, $user->fresh()->tokens);
        $this->assertDatabaseHas('token_transactions', ['user_id' => $user->id, 'type' => 'EXTENSION', 'amount' => -2]);
    }

    public function test_approve_fails_without_enough_tokens_and_changes_nothing(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 1]);
        $extension = $this->secondExtension($user);

        $this->approve($extension)
            ->assertStatus(422)
            ->assertJsonPath('reason', 'insufficient_tokens');

        $this->assertSame('PENDING', $extension->fresh()->status);
        $this->assertSame(1, $user->fresh()->tokens);
    }

    public function test_approve_fails_when_game_was_reserved_in_the_meantime(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 5]);
        $extension = $this->secondExtension($user);
        Reservation::factory()->create(['game_id' => $extension->loan->copy->game_id]);

        $this->approve($extension)
            ->assertStatus(409)
            ->assertJsonPath('reason', 'game_reserved');

        $this->assertSame('PENDING', $extension->fresh()->status);
        $this->assertSame(5, $user->fresh()->tokens);
    }

    public function test_rejecting_costs_nothing(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 5]);
        $extension = $this->secondExtension($user);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/reject")
            ->assertOk();

        $this->assertSame(5, $user->fresh()->tokens);
    }

    private function secondExtension(User $user): Extension
    {
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'EXTENDED']);
        Extension::factory()->approved()->create(['loan_id' => $loan->id]);

        return Extension::factory()->create(['loan_id' => $loan->id, 'status' => 'PENDING']);
    }

    private function approve(Extension $extension): TestResponse
    {
        return $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/approve");
    }
}

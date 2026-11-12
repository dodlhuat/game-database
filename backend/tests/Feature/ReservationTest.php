<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/reservations')->assertUnauthorized();
    }

    public function test_index_returns_user_reservations(): void
    {
        $user = User::factory()->member()->create();
        Reservation::factory()->count(2)->create(['user_id' => $user->id]);
        Reservation::factory()->create(); // other user

        $this->actingAs($user)
            ->getJson('/api/reservations')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_creates_reservation(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => $game->id])
            ->assertCreated();

        $this->assertDatabaseHas('reservations', ['game_id' => $game->id, 'user_id' => $user->id]);
    }

    public function test_store_prevents_duplicate_reservation(): void
    {
        $user = User::factory()->member()->create();
        $game = Game::factory()->create();
        Reservation::factory()->create(['game_id' => $game->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => $game->id])
            ->assertStatus(422);
    }

    public function test_destroy_removes_own_reservation(): void
    {
        $user = User::factory()->member()->create();
        $reservation = Reservation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/reservations/{$reservation->id}")
            ->assertOk();

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_destroy_denies_other_users_reservation(): void
    {
        $user = User::factory()->member()->create();
        $reservation = Reservation::factory()->create(); // other user

        $this->actingAs($user)
            ->deleteJson("/api/reservations/{$reservation->id}")
            ->assertForbidden();
    }

    public function test_reserving_charges_the_reservation_fee(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 5]);
        $game = Game::factory()->create(['title' => 'Catan']);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => $game->id])
            ->assertCreated();

        $this->assertSame(4, $user->fresh()->tokens);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => 'RESERVATION',
            'amount' => -1,
            'description' => 'Reservierung: Catan',
        ]);
    }

    public function test_reservation_fee_follows_the_setting(): void
    {
        LoanSetting::factory()->create(['reservation_cost' => 3]);
        $user = User::factory()->member()->create(['tokens' => 5]);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => Game::factory()->create()->id])
            ->assertCreated();

        $this->assertSame(2, $user->fresh()->tokens);
    }

    public function test_reserving_fails_without_free_tokens_and_creates_nothing(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 1, 'tokens_blocked' => 1]);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => Game::factory()->create()->id])
            ->assertStatus(402)
            ->assertJsonPath('reason', 'insufficient_tokens')
            ->assertJsonPath('reservation_cost', 1);

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('token_transactions', 0);
    }

    public function test_duplicate_reservation_is_not_charged(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 5]);
        $game = Game::factory()->create();
        Reservation::factory()->create(['game_id' => $game->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => $game->id])
            ->assertStatus(422);

        $this->assertSame(5, $user->fresh()->tokens);
    }

    public function test_cancelling_does_not_refund_the_fee(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->member()->create(['tokens' => 5]);
        $game = Game::factory()->create();

        $id = $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => $game->id])
            ->json('data.id');
        $this->actingAs($user)->deleteJson("/api/reservations/{$id}")->assertOk();

        $this->assertSame(4, $user->fresh()->tokens);
    }

    public function test_admin_reserves_for_free(): void
    {
        LoanSetting::factory()->create();
        $admin = User::factory()->admin()->create(['tokens' => 0]);

        $this->actingAs($admin)
            ->postJson('/api/reservations', ['game_id' => Game::factory()->create()->id])
            ->assertCreated();

        $this->assertDatabaseCount('token_transactions', 0);
    }

    public function test_non_member_cannot_reserve(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->create(['tokens' => 5]);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['game_id' => Game::factory()->create()->id])
            ->assertForbidden()
            ->assertJsonPath('reason', 'membership_required');
    }
}

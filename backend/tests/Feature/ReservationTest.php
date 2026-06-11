<?php

namespace Tests\Feature;

use App\Models\Game;
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
}

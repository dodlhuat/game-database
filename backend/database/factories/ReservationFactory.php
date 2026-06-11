<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reservation> */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'game_id'  => Game::factory(),
            'user_id'  => User::factory()->member(),
            'position' => 1,
        ];
    }
}

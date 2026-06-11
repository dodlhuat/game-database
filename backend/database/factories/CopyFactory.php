<?php

namespace Database\Factories;

use App\Models\Copy;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Copy> */
class CopyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'game_id'     => Game::factory(),
            'condition'   => 'NEW',
            'borrow_count' => 0,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['condition' => 'LOCKED']);
    }

    public function damaged(): static
    {
        return $this->state(fn () => ['condition' => 'DAMAGED']);
    }
}

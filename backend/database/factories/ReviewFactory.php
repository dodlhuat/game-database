<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Loan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory()->member(),
            'rating'  => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}

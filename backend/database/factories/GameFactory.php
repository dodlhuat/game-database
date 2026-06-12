<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Game> */
class GameFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title).'-'.fake()->randomNumber(5),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'min_players' => 2,
            'max_players' => 4,
            'min_age' => 10,
            'difficulty' => fake()->randomElement(['EASY', 'MEDIUM', 'HARD']),
            'deposit_tokens' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

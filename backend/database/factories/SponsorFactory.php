<?php

namespace Database\Factories;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sponsor> */
class SponsorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => null,
            'logo_url' => null,
            'logo_on_light' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

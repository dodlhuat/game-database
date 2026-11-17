<?php

namespace Database\Factories;

use App\Models\PickupLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PickupLocation> */
class PickupLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'address' => null,
            'opening_hours' => null,
            'notes' => null,
            'map_url' => null,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

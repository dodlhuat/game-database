<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'date'        => now()->addDays(7)->toDateString(),
            'time'        => '18:00',
            'is_all_day'  => false,
            'description' => fake()->paragraph(),
        ];
    }
}

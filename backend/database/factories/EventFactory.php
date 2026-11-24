<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\PickupLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'date' => now()->addDays(7)->toDateString(),
            'time' => '18:00',
            'end_time' => '20:00',
            'is_all_day' => false,
            'description' => fake()->paragraph(),
        ];
    }

    /** A personal pickup event at the given location (a personal one is created when omitted). */
    public function pickupAt(?PickupLocation $location = null, ?int $maxLoans = null): static
    {
        return $this->state(fn () => [
            'pickup_location_id' => $location->id ?? PickupLocation::factory()->create(['is_personal' => true])->id,
            'max_loans' => $maxLoans,
        ]);
    }
}

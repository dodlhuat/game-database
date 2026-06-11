<?php

namespace Database\Factories;

use App\Models\Extension;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Extension> */
class ExtensionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id'            => Loan::factory(),
            'requested_at'       => now(),
            'requested_due_date' => now()->addWeeks(4)->toDateString(),
            'status'             => 'PENDING',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'APPROVED']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'REJECTED']);
    }
}

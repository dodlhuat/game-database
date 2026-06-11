<?php

namespace Database\Factories;

use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Loan> */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'copy_id'        => Copy::factory(),
            'user_id'        => User::factory()->member(),
            'start_date'     => now()->toDateString(),
            'due_date'       => now()->addWeeks(4)->toDateString(),
            'status'         => 'ACTIVE',
            'deposit_tokens' => 0,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'status'      => 'RETURNED',
            'returned_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status'   => 'OVERDUE',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
    }
}

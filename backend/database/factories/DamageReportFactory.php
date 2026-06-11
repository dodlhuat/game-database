<?php

namespace Database\Factories;

use App\Models\DamageReport;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DamageReport> */
class DamageReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id'     => Loan::factory(),
            'user_id'     => User::factory()->member(),
            'description' => fake()->sentence(),
        ];
    }
}

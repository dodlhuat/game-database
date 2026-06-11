<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PackageLoan> */
class PackageLoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'package_id'  => Package::factory(),
            'user_id'     => User::factory()->member(),
            'start_date'  => now()->toDateString(),
            'due_date'    => now()->addWeeks(4)->toDateString(),
            'status'      => 'ACTIVE',
        ];
    }
}

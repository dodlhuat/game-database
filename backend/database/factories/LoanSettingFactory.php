<?php

namespace Database\Factories;

use App\Models\LoanSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoanSetting> */
class LoanSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'start_date' => now()->toDateString(),
            'interval_days' => 14,
            'grace_days' => 3,
            'loan_duration_weeks' => 4,
            'max_extensions' => 2,
            'loan_cost' => 2,
            'condition_very_good_after' => 5,
            'condition_good_after' => 50,
            'deposit_pct_very_good' => 90,
            'deposit_pct_good' => 80,
        ];
    }
}

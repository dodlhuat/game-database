<?php

namespace Database\Seeders;

use App\Models\LoanSetting;
use Illuminate\Database\Seeder;

class LoanSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (LoanSetting::count() === 0) {
            LoanSetting::create([
                'start_date'          => now()->toDateString(),
                'interval_days'       => 14,
                'grace_days'          => 3,
                'loan_duration_weeks' => 4,
            ]);
        }
    }
}

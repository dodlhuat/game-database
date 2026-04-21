<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSetting extends Model
{
    protected $fillable = [
        'start_date',
        'interval_days',
        'grace_days',
        'loan_duration_weeks',
        'max_extensions',
        'loan_cost',
        'condition_very_good_after',
        'condition_good_after',
        'deposit_pct_very_good',
        'deposit_pct_good',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'start_date'               => now()->toDateString(),
            'interval_days'            => 14,
            'grace_days'               => 3,
            'loan_duration_weeks'      => 4,
            'max_extensions'           => 2,
            'loan_cost'                => 2,
            'condition_very_good_after' => 5,
            'condition_good_after'     => 50,
            'deposit_pct_very_good'    => 90,
            'deposit_pct_good'         => 80,
        ]);
    }
}

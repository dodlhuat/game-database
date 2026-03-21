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
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'start_date'          => now()->toDateString(),
            'interval_days'       => 14,
            'grace_days'          => 3,
            'loan_duration_weeks' => 4,
        ]);
    }
}

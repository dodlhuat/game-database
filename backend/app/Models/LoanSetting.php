<?php

namespace App\Models;

use Database\Factories\LoanSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LoanSetting extends Model
{
    /** @use HasFactory<LoanSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'start_date',
        'interval_days',
        'grace_days',
        'loan_duration_weeks',
        'max_extensions',
        'loan_cost_small',
        'loan_cost_medium',
        'loan_cost_large',
        'package_loan_cost',
        'extension_cost',
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
            'start_date' => now()->toDateString(),
            'interval_days' => 14,
            'grace_days' => 3,
            'loan_duration_weeks' => 2,
            'max_extensions' => 2,
            'loan_cost_small' => 3,
            'loan_cost_medium' => 6,
            'loan_cost_large' => 10,
            'package_loan_cost' => 3,
            'extension_cost' => 2,
            'condition_very_good_after' => 5,
            'condition_good_after' => 50,
            'deposit_pct_very_good' => 90,
            'deposit_pct_good' => 80,
        ]);
    }

    /** Loads the settings once per request, so resource collections don't query per item. */
    public static function forRequest(Request $request): self
    {
        $setting = $request->attributes->get('loan_setting');

        if (! $setting instanceof self) {
            $setting = self::instance();
            $request->attributes->set('loan_setting', $setting);
        }

        return $setting;
    }

    /** Loan price of a single game, based on its size category. */
    public function costFor(?string $sizeCategory): int
    {
        return match ($sizeCategory) {
            'SMALL' => $this->loan_cost_small,
            'LARGE' => $this->loan_cost_large,
            default => $this->loan_cost_medium,
        };
    }
}

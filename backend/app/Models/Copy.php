<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Copy extends Model
{
    protected $fillable = [
        'game_id',
        'condition',
        'borrow_count',
        'qr_code',
        'notes',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE']);
    }

    public function calculateDeposit(Game $game, LoanSetting $setting): int
    {
        if ($game->deposit_tokens <= 0) {
            return 0;
        }

        return match ($this->condition) {
            'VERY_GOOD' => (int) round($game->deposit_tokens * $setting->deposit_pct_very_good / 100),
            'GOOD'      => (int) round($game->deposit_tokens * $setting->deposit_pct_good / 100),
            default     => $game->deposit_tokens, // NEW = 100%
        };
    }

    public function resolveConditionFromBorrowCount(LoanSetting $setting): string
    {
        if ($this->borrow_count >= $setting->condition_good_after) {
            return 'GOOD';
        }
        if ($this->borrow_count >= $setting->condition_very_good_after) {
            return 'VERY_GOOD';
        }
        return 'NEW';
    }
}

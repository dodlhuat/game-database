<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipCancellation extends Model
{
    protected $fillable = [
        'user_id',
        'previous_role',
        'tokens_total',
        'bonus_tokens_forfeited',
        'refund_tokens',
        'gross_cents',
        'fee_cents',
        'refund_cents',
        'account_holder',
        'iban',
        'reason',
        'status',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'iban' => 'encrypted',
            'refunded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

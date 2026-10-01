<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenLot extends Model
{
    public const KIND_NORMAL = 'NORMAL';

    public const KIND_BONUS = 'BONUS';

    protected $fillable = [
        'user_id',
        'kind',
        'source',
        'amount',
        'remaining',
        'unit_cents',
        'acquired_at',
        'refundable_after',
        'expires_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'remaining' => 'integer',
            'unit_cents' => 'integer',
            'acquired_at' => 'datetime',
            'refundable_after' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

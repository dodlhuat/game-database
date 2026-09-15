<?php

namespace App\Models;

use Database\Factories\TokenPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TokenPurchase extends Model
{
    /** @use HasFactory<TokenPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'paypal_order_id',
        'paypal_capture_id',
        'token_amount',
        'price_cents',
        'currency',
        'status',
        'payload',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<TokenTransaction, $this> */
    public function tokenTransaction(): HasOne
    {
        return $this->hasOne(TokenTransaction::class);
    }
}

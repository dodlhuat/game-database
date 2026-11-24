<?php

namespace App\Models;

use Database\Factories\TokenPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $provider mollie | stripe (older payments)
 * @property string|null $provider_payment_id
 * @property string $status
 * @property int $price_cents
 * @property string $currency
 */
class TokenPurchase extends Model
{
    /** @use HasFactory<TokenPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_payment_id',
        'provider_charge_id',
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

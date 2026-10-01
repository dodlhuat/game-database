<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPayment extends Model
{
    public const TYPE_MEMBER = 'MEMBER';

    public const TYPE_SUPPORTER = 'SUPPORTER';

    public const TYPE_RENEWAL = 'RENEWAL';

    protected $fillable = [
        'user_id',
        'type',
        'provider_payment_intent_id',
        'provider_charge_id',
        'price_cents',
        'currency',
        'status',
        'street',
        'postal_code',
        'city',
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
}

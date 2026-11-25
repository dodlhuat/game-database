<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $provider mollie | stripe (older payments)
 * @property string|null $provider_payment_id
 * @property string $status
 * @property int $price_cents
 * @property string $currency
 */
class MembershipPayment extends Model
{
    public const TYPE_MEMBER = 'MEMBER';

    public const TYPE_SUPPORTER = 'SUPPORTER';

    public const TYPE_RENEWAL = 'RENEWAL';

    /**
     * Paid, but the membership it was for already existed by then (two
     * tabs, two clicks at once): not applied, and waiting for an admin to
     * refund it — money always goes back by hand. DUP_REFUNDED once that
     * happened (marked in the admin, or seen as refunded at Mollie).
     */
    public const STATUS_DUPLICATE = 'DUPLICATE';

    public const STATUS_DUP_REFUNDED = 'DUP_REFUNDED';

    public function isDuplicate(): bool
    {
        return in_array($this->status, [self::STATUS_DUPLICATE, self::STATUS_DUP_REFUNDED], true);
    }

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'provider_payment_id',
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

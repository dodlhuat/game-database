<?php

namespace App\Models;

use Database\Factories\PackageLoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $due_date
 * @property Carbon|null $start_date
 * @property Carbon|null $returned_at
 * @property Carbon|null $handed_out_at
 * @property int|null $pickup_event_id
 * @property list<array{lot_id: int|null, amount: int}>|null $spend_receipt
 */
class PackageLoan extends Model
{
    /** @use HasFactory<PackageLoanFactory> */
    use HasFactory;

    /** Every loan view shows where the game goes back to — and the pickup appointment, if booked. */
    protected $with = ['pickupLocation', 'pickupEvent'];

    protected $fillable = [
        'package_id',
        'user_id',
        'pickup_location_id',
        'pickup_event_id',
        'handed_out_at',
        'spend_receipt',
        'cancelled_at',
        'start_date',
        'due_date',
        'returned_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'handed_out_at' => 'datetime',
            'spend_receipt' => 'array',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isAwaitingPickup(): bool
    {
        return $this->pickup_event_id !== null && $this->handed_out_at === null && $this->status === 'ACTIVE';
    }

    /**
     * The personal pickup event this loan was booked for, if any.
     *
     * @return BelongsTo<Event, $this>
     */
    public function pickupEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'pickup_event_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Where the package is picked up and has to be returned.
     *
     * @return BelongsTo<PickupLocation, $this>
     */
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class)->withTrashed();
    }

    /** @return HasMany<Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}

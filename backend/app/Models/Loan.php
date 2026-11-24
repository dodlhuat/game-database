<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $due_date
 * @property Carbon|null $start_date
 * @property Carbon|null $returned_at
 * @property Carbon|null $handed_out_at
 * @property int|null $pickup_event_id
 */
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /** Statuses in which the game has not come back yet. */
    public const OPEN_STATUSES = ['ACTIVE', 'EXTENDED', 'OVERDUE'];

    /** Every loan view shows where the game goes back to — and the pickup appointment, if booked. */
    protected $with = ['pickupLocation', 'pickupEvent'];

    protected $fillable = [
        'copy_id',
        'user_id',
        'package_loan_id',
        'pickup_location_id',
        'pickup_event_id',
        'handed_out_at',
        'pickup_reminder_sent_at',
        'start_date',
        'due_date',
        'returned_at',
        'overdue_reminder_sent_at',
        'return_condition',
        'deposit_tokens',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'handed_out_at' => 'datetime',
            'pickup_reminder_sent_at' => 'datetime',
            'overdue_reminder_sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Copy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    /** @return BelongsTo<PackageLoan, $this> */
    public function packageLoan(): BelongsTo
    {
        return $this->belongsTo(PackageLoan::class);
    }

    /**
     * Where the game is picked up and has to be returned.
     *
     * @return BelongsTo<PickupLocation, $this>
     */
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class)->withTrashed();
    }

    /**
     * Booked for a pickup event that has not handed the game out yet: it
     * cannot be extended or returned, and it is not overdue.
     */
    public function isAwaitingPickup(): bool
    {
        return $this->pickup_event_id !== null
            && $this->handed_out_at === null
            && in_array($this->status, self::OPEN_STATUSES, true);
    }

    /**
     * Open loans that are actually out with the member.
     *
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeHandedOut(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('pickup_event_id')->orWhereNotNull('handed_out_at'));
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

    /** @return HasMany<Extension, $this> */
    public function extensions(): HasMany
    {
        return $this->hasMany(Extension::class);
    }

    /** @return HasOne<Review, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /** @return HasMany<DamageReport, $this> */
    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }
}

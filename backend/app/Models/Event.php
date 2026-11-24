<?php

namespace App\Models;

use App\Casts\StorageUrl;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Carbon $date
 * @property string|null $time
 * @property string|null $end_time
 * @property int|null $max_loans
 * @property int|null $single_bookings_count from withCount() in bookableFor()
 * @property int|null $package_bookings_count from withCount() in bookableFor()
 * @property int|null $bookings_count set by bookableFor()
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'date',
        'time',
        'end_time',
        'is_all_day',
        'pickup_location_id',
        'max_loans',
        'description',
        'image_url',
    ];

    protected $casts = [
        'date' => 'date',
        'is_all_day' => 'boolean',
        'max_loans' => 'integer',
        'image_url' => StorageUrl::class,
    ];

    /**
     * The personal pickup location this event is held at — set only for
     * pickup events.
     *
     * @return BelongsTo<PickupLocation, $this>
     */
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class)->withTrashed();
    }

    /**
     * Single-game loans booked for this pickup event (package games are
     * counted through their package).
     *
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'pickup_event_id')
            ->whereNull('package_loan_id')
            ->where('status', '!=', 'CANCELLED');
    }

    /** @return HasMany<PackageLoan, $this> */
    public function packageLoans(): HasMany
    {
        return $this->hasMany(PackageLoan::class, 'pickup_event_id')->where('status', '!=', 'CANCELLED');
    }

    public function isPickup(): bool
    {
        return $this->pickup_location_id !== null;
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePickup(Builder $query): Builder
    {
        return $query->whereNotNull('pickup_location_id');
    }

    /** A package counts as one booking, like a single game. */
    public function bookingsCount(): int
    {
        return $this->loans()->count() + $this->packageLoans()->count();
    }

    public function startsAt(): Carbon
    {
        return $this->date->copy()->setTimeFromTimeString($this->time ?? '00:00');
    }

    /** Last moment a member can still book this pickup event. */
    public function bookingClosesAt(LoanSetting $setting): Carbon
    {
        return $this->startsAt()->subHours($setting->pickup_event_lead_hours);
    }

    /** Free places, or null when the event has no cap. */
    public function spotsLeft(?int $bookings = null): ?int
    {
        if ($this->max_loans === null) {
            return null;
        }

        return max(0, $this->max_loans - ($bookings ?? $this->bookingsCount()));
    }

    /**
     * Pickup events the user can book right now: upcoming, booking still
     * open, not full, at an active personal location open to the user.
     *
     * @return Collection<int, Event>
     */
    public static function bookableFor(User $user, LoanSetting $setting): Collection
    {
        return self::pickup()
            ->whereDate('date', '>=', today())
            ->whereHas('pickupLocation', fn (Builder $q) => $q
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->where('is_personal', true)
                ->accessibleTo($user))
            ->with('pickupLocation')
            ->withCount([
                'loans as single_bookings_count',
                'packageLoans as package_bookings_count',
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->filter(function (Event $event) use ($setting) {
                $bookings = ($event->single_bookings_count ?? 0) + ($event->package_bookings_count ?? 0);
                $event->setAttribute('bookings_count', $bookings);

                return now()->lt($event->bookingClosesAt($setting))
                    && $event->spotsLeft($bookings) !== 0;
            })
            ->values();
    }
}

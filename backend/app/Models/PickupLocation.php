<?php

namespace App\Models;

use Database\Factories\PickupLocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A place where members pick up borrowed games — and return them again.
 */
class PickupLocation extends Model
{
    /** @use HasFactory<PickupLocationFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'opening_hours',
        'notes',
        'map_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @param  Builder<PickupLocation>  $query
     * @return Builder<PickupLocation>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<PickupLocation>  $query
     * @return Builder<PickupLocation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Locations the user may pick: every location without assigned users,
     * plus the ones the user is assigned to. Admins may use all of them.
     *
     * @param  Builder<PickupLocation>  $query
     * @return Builder<PickupLocation>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereDoesntHave('users')
            ->orWhereHas('users', fn (Builder $u) => $u->whereKey($user->id)));
    }

    public function isAccessibleTo(User $user): bool
    {
        return self::accessibleTo($user)->whereKey($this->id)->exists();
    }

    /**
     * The location a new loan uses: the chosen one if it is active and open
     * to the user, or — when no choice was sent — the only such location.
     */
    public static function forNewLoan(mixed $id, User $user): ?self
    {
        $candidates = self::active()->accessibleTo($user);

        if ($id === null || $id === '') {
            $usable = $candidates->limit(2)->get();

            return $usable->count() === 1 ? $usable->first() : null;
        }

        return is_numeric($id) ? $candidates->find((int) $id) : null;
    }

    /**
     * Users this location is restricted to — none means open for everyone.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return HasMany<Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** @return HasMany<PackageLoan, $this> */
    public function packageLoans(): HasMany
    {
        return $this->hasMany(PackageLoan::class);
    }

    /**
     * Borrowed games that still have to come back here. Games of a package
     * loan carry the location on their own loan row, so they count too.
     */
    public function openLoanCount(): int
    {
        return $this->loans()->whereIn('status', Loan::OPEN_STATUSES)->count();
    }
}

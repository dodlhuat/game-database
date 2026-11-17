<?php

namespace App\Models;

use Database\Factories\PickupLocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * The location a new loan uses: the chosen one if it is active, or — when
     * no choice was sent — the only active location, if there is just one.
     */
    public static function forNewLoan(mixed $id): ?self
    {
        if ($id === null || $id === '') {
            $active = self::active()->limit(2)->get();

            return $active->count() === 1 ? $active->first() : null;
        }

        return is_numeric($id) ? self::active()->find((int) $id) : null;
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

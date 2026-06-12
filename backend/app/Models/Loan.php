<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $due_date
 * @property Carbon|null $start_date
 */
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $fillable = [
        'copy_id',
        'user_id',
        'package_loan_id',
        'start_date',
        'due_date',
        'returned_at',
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

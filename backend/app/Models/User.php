<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property 'USER'|'MEMBER'|'ADMIN' $role
 * @property Carbon|null $membership_expires_at
 * @property Carbon|null $date_of_birth
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'address',
        'phone',
        'date_of_birth',
        'password',
        'role',
        'status',
        'newsletter_opt_in',
        'terms_accepted_at',
        'terms_version',
        'tokens',
        'tokens_blocked',
        'membership_expires_at',
        'renewal_reminder_sent_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'membership_expires_at' => 'datetime',
            'renewal_reminder_sent_at' => 'datetime',
            'password' => 'hashed',
            'newsletter_opt_in' => 'boolean',
            'tokens' => 'integer',
            'tokens_blocked' => 'integer',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isRegisteredUser(): bool
    {
        return $this->role === 'USER';
    }

    public function isMember(): bool
    {
        return $this->role === 'MEMBER'
            && $this->membership_expires_at !== null
            && $this->membership_expires_at->isFuture();
    }

    public function hasEnoughTokens(int $cost): bool
    {
        return $this->tokens >= $cost;
    }

    public function freeTokens(): int
    {
        return max(0, $this->tokens - $this->tokens_blocked);
    }

    public function hasEnoughFreeTokens(int $cost): bool
    {
        return $this->freeTokens() >= $cost;
    }

    // Relations
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

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** @return HasMany<DamageReport, $this> */
    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    /** @return HasMany<TokenTransaction, $this> */
    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }
}

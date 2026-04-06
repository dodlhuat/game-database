<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'address',
        'password',
        'role',
        'status',
        'newsletter_opt_in',
        'terms_accepted_at',
        'terms_version',
        'tokens',
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
            'email_verified_at'        => 'datetime',
            'terms_accepted_at'        => 'datetime',
            'membership_expires_at'    => 'datetime',
            'renewal_reminder_sent_at' => 'datetime',
            'password'                 => 'hashed',
            'newsletter_opt_in'        => 'boolean',
            'tokens'                   => 'integer',
        ];
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

    // Relations
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function packageLoans(): HasMany
    {
        return $this->hasMany(PackageLoan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }
}

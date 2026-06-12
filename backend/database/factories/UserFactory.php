<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'USER',
            'status' => 'ACTIVE',
            'tokens' => 0,
            'tokens_blocked' => 0,
            'newsletter_opt_in' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'ADMIN', 'status' => 'ACTIVE']);
    }

    public function member(): static
    {
        return $this->state(fn () => [
            'role' => 'MEMBER',
            'status' => 'ACTIVE',
            'membership_expires_at' => now()->addYear(),
            'tokens' => 50,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'SUSPENDED']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'PENDING']);
    }
}

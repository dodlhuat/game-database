<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Removes a user's personal data for good: name and e-mail become
 * placeholders, address and logins go, the account stays suspended. Loans
 * and payments keep pointing at the row, so the bookkeeping stays intact.
 * Used for long-inactive accounts and for deleted ones past their grace period.
 */
class AccountAnonymizer
{
    public function anonymize(User $user): void
    {
        DB::transaction(function () use ($user) {
            $email = $user->email;

            $user->tokens()->delete();
            $user->favorites()->delete();
            $user->reservations()->delete();
            $user->reviews()->delete();
            $user->emailLogs()->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => 'Gelöschtes Konto',
                'email' => "deleted-{$user->id}@anonymized.invalid",
                'address' => null,
                'street' => null,
                'postal_code' => null,
                'city' => null,
                'date_of_birth' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'newsletter_opt_in' => false,
                'status' => 'SUSPENDED',
                'anonymized_at' => now(),
            ])->saveQuietly();
        });
    }
}

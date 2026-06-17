<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    public function return(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    public function extend(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }
}

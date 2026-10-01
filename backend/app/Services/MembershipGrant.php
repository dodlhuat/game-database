<?php

namespace App\Services;

use App\Models\User;

/** Tokens and bonus tokens a full membership comes with. */
class MembershipGrant
{
    public function __construct(private TokenWallet $wallet) {}

    public function grantMemberPackage(User $user): void
    {
        $this->wallet->grantTokens(
            $user,
            (int) config('membership.tokens'),
            'MEMBERSHIP',
            (int) config('tokens.token_value_cents'),
        );

        $this->wallet->grantBonus(
            $user,
            (int) config('membership.bonus_tokens'),
            'MEMBERSHIP',
            now()->addMonths((int) config('membership.bonus_valid_months')),
            'Bonus-Token zur Mitgliedschaft',
        );
    }
}

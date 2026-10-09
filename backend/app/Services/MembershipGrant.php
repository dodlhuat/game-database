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
            config()->integer('membership.tokens'),
            'MEMBERSHIP',
            config()->integer('tokens.token_value_cents'),
        );

        $this->wallet->grantBonus(
            $user,
            config()->integer('membership.bonus_tokens'),
            'MEMBERSHIP',
            now()->addMonths(config()->integer('membership.bonus_valid_months')),
            'Bonus-Token zur Mitgliedschaft',
        );
    }
}

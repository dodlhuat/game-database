<?php

namespace App\Services;

use App\Models\TokenLot;
use App\Models\TokenTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Single place that changes token balances.
 *
 * `users.tokens` is the cached sum of normal tokens, `users.bonus_tokens` the
 * cached sum of non-expired bonus tokens. Both are backed by `token_lots`.
 * Tokens that exist on `users.tokens` without a lot (e.g. set directly) are
 * treated as worthless legacy tokens and are spent first.
 */
class TokenWallet
{
    /**
     * Credit normal tokens as a new lot. `$unitCents` is what one token cost
     * and decides how much is refunded when the membership is cancelled.
     */
    public function grantTokens(
        User $user,
        int $amount,
        string $source,
        int $unitCents,
        ?CarbonInterface $refundableAfter = null,
    ): TokenLot {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $source, $unitCents, $refundableAfter) {
            $lot = TokenLot::create([
                'user_id' => $user->id,
                'kind' => TokenLot::KIND_NORMAL,
                'source' => $source,
                'amount' => $amount,
                'remaining' => $amount,
                'unit_cents' => $unitCents,
                'acquired_at' => now(),
                'refundable_after' => $refundableAfter,
            ]);

            User::whereKey($user->id)->increment('tokens', $amount);
            $user->refresh();

            return $lot;
        });
    }

    /** Credit bonus tokens that expire at `$expiresAt`. */
    public function grantBonus(User $user, int $amount, string $source, CarbonInterface $expiresAt, ?string $description = null): TokenLot
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $source, $expiresAt, $description) {
            $lot = TokenLot::create([
                'user_id' => $user->id,
                'kind' => TokenLot::KIND_BONUS,
                'source' => $source,
                'amount' => $amount,
                'remaining' => $amount,
                'unit_cents' => 0,
                'acquired_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            User::whereKey($user->id)->increment('bonus_tokens', $amount);

            TokenTransaction::create([
                'user_id' => $user->id,
                'token_lot_id' => $lot->id,
                'type' => 'BONUS_GRANT',
                'amount' => $amount,
                'description' => $description ?? 'Bonus-Token',
            ]);

            $user->refresh();

            return $lot;
        });
    }

    /**
     * Consume tokens: bonus first (earliest expiry), then normal tokens
     * (worthless legacy first, then oldest lot). Callers must check the
     * balance beforehand.
     */
    public function spend(User $user, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount) {
            /** @var User $locked */
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($locked->tokens + $locked->bonus_tokens < $amount) {
                throw new RuntimeException('Insufficient tokens.');
            }

            $left = $amount;
            $bonusUsed = 0;
            $normalUsed = 0;

            $bonusLots = TokenLot::where('user_id', $locked->id)
                ->where('kind', TokenLot::KIND_BONUS)
                ->where('remaining', '>', 0)
                ->whereNull('expired_at')
                ->orderBy('expires_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($bonusLots as $lot) {
                if ($left === 0) {
                    break;
                }
                $take = min($left, $lot->remaining);
                $lot->decrement('remaining', $take);
                $left -= $take;
                $bonusUsed += $take;
            }

            if ($left > 0) {
                $normalLots = TokenLot::where('user_id', $locked->id)
                    ->where('kind', TokenLot::KIND_NORMAL)
                    ->where('remaining', '>', 0)
                    ->orderBy('acquired_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // Tokens without a lot are worthless legacy tokens: spend them first.
                $legacy = max(0, $locked->tokens - (int) $normalLots->sum('remaining'));
                $take = min($left, $legacy);
                $left -= $take;
                $normalUsed += $take;

                foreach ($normalLots as $lot) {
                    if ($left === 0) {
                        break;
                    }
                    $take = min($left, $lot->remaining);
                    $lot->decrement('remaining', $take);
                    $left -= $take;
                    $normalUsed += $take;
                }
            }

            if ($bonusUsed > 0) {
                $locked->decrement('bonus_tokens', $bonusUsed);
            }
            if ($normalUsed > 0) {
                $locked->decrement('tokens', $normalUsed);
            }
        });

        $user->refresh();
    }

    /**
     * Expire all bonus lots that are past their expiry date.
     *
     * @return int number of expired lots
     */
    public function expireBonus(): int
    {
        $count = 0;

        TokenLot::where('kind', TokenLot::KIND_BONUS)
            ->whereNull('expired_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->each(function (TokenLot $lot) use (&$count) {
                DB::transaction(function () use ($lot, &$count) {
                    $fresh = TokenLot::whereKey($lot->id)->lockForUpdate()->first();
                    if (! $fresh || $fresh->expired_at !== null) {
                        return;
                    }

                    $remaining = $fresh->remaining;
                    $fresh->update(['remaining' => 0, 'expired_at' => now()]);

                    if ($remaining > 0) {
                        User::whereKey($fresh->user_id)->decrement('bonus_tokens', $remaining);
                        TokenTransaction::create([
                            'user_id' => $fresh->user_id,
                            'token_lot_id' => $fresh->id,
                            'type' => 'BONUS_EXPIRE',
                            'amount' => -$remaining,
                            'description' => 'Bonus-Token verfallen',
                        ]);
                    }

                    $count++;
                });
            });

        return $count;
    }
}

<?php

namespace App\Services;

use App\Models\MembershipCancellation;
use App\Models\TokenLot;
use App\Models\User;
use App\Rules\Iban;
use Illuminate\Support\Facades\DB;

class MembershipCancellationService
{
    public function __construct(private TokenWallet $wallet) {}

    /**
     * What a cancellation would look like right now. Only tokens that were
     * actually paid for (unit price > 0) and are past their waiting period
     * are refunded, valued at the price paid, minus the processing fee.
     *
     * @return array{tokens: int, bonus_tokens: int, refund_tokens: int, gross_cents: int, fee_cents: int, refund_cents: int, non_refundable_tokens: int, blockers: array<int, string>}
     */
    public function preview(User $user): array
    {
        $lots = TokenLot::where('user_id', $user->id)
            ->where('kind', TokenLot::KIND_NORMAL)
            ->where('remaining', '>', 0)
            ->where('unit_cents', '>', 0)
            ->where(fn ($q) => $q->whereNull('refundable_after')->orWhere('refundable_after', '<=', now()))
            ->get();

        $refundTokens = (int) $lots->sum('remaining');
        $gross = (int) $lots->sum(fn (TokenLot $lot) => $lot->remaining * $lot->unit_cents);
        $fee = min($gross, (int) config('membership.refund_fee_cents'));

        return [
            'tokens' => $user->tokens,
            'bonus_tokens' => $user->bonus_tokens,
            'refund_tokens' => $refundTokens,
            'gross_cents' => $gross,
            'fee_cents' => $fee,
            'refund_cents' => $gross - $fee,
            'non_refundable_tokens' => max(0, $user->tokens - $refundTokens),
            'blockers' => $this->blockers($user),
        ];
    }

    /** @return array<int, string> */
    public function blockers(User $user): array
    {
        $blockers = [];

        if ($user->loans()->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])->exists()
            || $user->packageLoans()->where('status', 'ACTIVE')->exists()) {
            $blockers[] = 'open_loans';
        }

        if ($user->tokens_blocked > 0) {
            $blockers[] = 'blocked_tokens';
        }

        return $blockers;
    }

    /**
     * @param  array{account_holder?: string|null, iban?: string|null, reason?: string|null}  $data
     *
     * @throws \RuntimeException when the cancellation is no longer possible
     */
    public function cancel(User $user, array $data): MembershipCancellation
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var User $locked */
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->role, ['MEMBER', 'SUPPORTER'], true)) {
                throw new \RuntimeException('not_a_member');
            }

            $preview = $this->preview($locked);
            if ($preview['blockers'] !== []) {
                throw new \RuntimeException('blocked');
            }

            $previousRole = $locked->role;
            $this->wallet->closeAccount($locked);

            $locked->update([
                'role' => 'USER',
                'membership_expires_at' => null,
                'renewal_reminder_sent_at' => null,
            ]);

            $hasRefund = $preview['refund_cents'] > 0;

            return MembershipCancellation::create([
                'user_id' => $locked->id,
                'previous_role' => $previousRole,
                'tokens_total' => $preview['tokens'],
                'bonus_tokens_forfeited' => $preview['bonus_tokens'],
                'refund_tokens' => $preview['refund_tokens'],
                'gross_cents' => $preview['gross_cents'],
                'fee_cents' => $preview['fee_cents'],
                'refund_cents' => $preview['refund_cents'],
                'account_holder' => $hasRefund ? $data['account_holder'] ?? null : null,
                'iban' => $hasRefund ? Iban::normalize((string) ($data['iban'] ?? '')) : null,
                'reason' => $data['reason'] ?? null,
                'status' => $hasRefund ? 'REQUESTED' : 'NO_REFUND',
            ]);
        });
    }
}

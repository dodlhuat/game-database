<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

/** Single source of truth for whether a loan can be extended and what it costs. */
class ExtensionPolicy
{
    public const BLOCK_NOT_EXTENDABLE = 'not_extendable';

    public const BLOCK_MAX_REACHED = 'max_reached';

    public const BLOCK_RESERVED = 'reserved';

    /** The first extension of a loan is free, every further one costs `extension_cost`. */
    public function nextCost(Loan $loan, LoanSetting $setting): int
    {
        return $loan->extensions->where('status', 'APPROVED')->isEmpty() ? 0 : $setting->extension_cost;
    }

    /** New due dates are counted from the current due date, or from today if it has passed. */
    public static function earliestBase(Loan $loan): Carbon
    {
        return $loan->due_date->isFuture() ? $loan->due_date->copy() : today();
    }

    public function blockReason(Loan $loan, LoanSetting $setting): ?string
    {
        if (! in_array($loan->status, ['ACTIVE', 'EXTENDED', 'OVERDUE'])) {
            return self::BLOCK_NOT_EXTENDABLE;
        }

        if ($loan->extensions->where('status', '!=', 'REJECTED')->count() >= $setting->max_extensions) {
            return self::BLOCK_MAX_REACHED;
        }

        return $this->isReservedByOthers($loan) ? self::BLOCK_RESERVED : null;
    }

    /** Someone else is on the waiting list. The borrower's own reservation does not count. */
    public function isReservedByOthers(Loan $loan): bool
    {
        return $loan->copy?->game?->reservations
            ->contains(fn (Reservation $reservation) => $reservation->user_id !== $loan->user_id) ?? false;
    }
}

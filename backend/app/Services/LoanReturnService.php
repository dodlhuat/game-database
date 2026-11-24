<?php

namespace App\Services;

use App\Models\Copy;
use App\Models\Event;
use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\User;
use App\Notifications\LoanReturnConfirmation;
use App\Notifications\LoanReturnedAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Books a return: the loan is closed and every copy waits at the pickup
 * location for a check (condition REVIEW). The borrower gets a receipt; when
 * the borrower confirmed it themselves, the admins learn that something is
 * ready to be collected.
 */
class LoanReturnService
{
    public const AWAITING_PICKUP_MESSAGE = 'Dieses Spiel wurde noch nicht abgeholt und kann daher nicht zurückgegeben werden.';

    /**
     * @param  PickupLocation|null  $where  where it came back; defaults to the pickup place
     * @param  Event|null  $atEvent  the pickup event it was handed back at
     */
    public function returnLoan(Loan $loan, string $condition, User $actor, ?PickupLocation $where = null, ?Event $atEvent = null): Loan
    {
        DB::transaction(function () use ($loan, $condition, $where, $atEvent) {
            $this->close($loan, $condition, $where, $atEvent);
        });

        $loan->load(['copy.game', 'user', 'pickupLocation', 'returnLocation']);
        $this->notify($loan, $actor);

        return $loan;
    }

    public function returnPackage(PackageLoan $packageLoan, string $condition, User $actor, ?PickupLocation $where = null, ?Event $atEvent = null): PackageLoan
    {
        DB::transaction(function () use ($packageLoan, $condition, $where, $atEvent) {
            $packageLoan->loans()
                ->with('copy')
                ->whereIn('status', Loan::OPEN_STATUSES)
                ->get()
                ->each(fn (Loan $loan) => $this->close($loan, $condition, $where, $atEvent));

            $packageLoan->update(['status' => 'RETURNED', 'returned_at' => now()]);
        });

        $packageLoan->load(['package', 'loans.copy.game', 'loans.returnLocation', 'user', 'pickupLocation']);
        $this->notify($packageLoan, $actor);

        return $packageLoan;
    }

    private function close(Loan $loan, string $condition, ?PickupLocation $where, ?Event $atEvent): void
    {
        $loan->update([
            'status' => 'RETURNED',
            'returned_at' => now(),
            'return_condition' => $condition,
            'return_location_id' => $where->id ?? $loan->pickup_location_id,
            'return_event_id' => $atEvent?->id,
        ]);

        /** @var Copy $copy */
        $copy = $loan->copy;
        $copy->update(['condition' => 'REVIEW']);
    }

    /** The return is booked already — a mail hiccup must not turn it into an error. */
    private function notify(Loan|PackageLoan $loan, User $actor): void
    {
        /** @var User $borrower */
        $borrower = $loan->user;

        try {
            $borrower->notify(new LoanReturnConfirmation($loan));

            if ($actor->id === $borrower->id) {
                $admins = User::where('role', 'ADMIN')->where('id', '!=', $borrower->id)->get();
                Notification::send($admins, new LoanReturnedAdmin($loan));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}

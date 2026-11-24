<?php

namespace App\Services;

use App\Models\Copy;
use App\Models\Loan;
use App\Models\PackageLoan;
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
    public function returnLoan(Loan $loan, string $condition, User $actor): Loan
    {
        DB::transaction(function () use ($loan, $condition) {
            $this->close($loan, $condition);
        });

        $loan->load(['copy.game', 'user', 'pickupLocation']);
        $this->notify($loan, $actor);

        return $loan;
    }

    public function returnPackage(PackageLoan $packageLoan, string $condition, User $actor): PackageLoan
    {
        DB::transaction(function () use ($packageLoan, $condition) {
            $packageLoan->loans()
                ->with('copy')
                ->whereIn('status', Loan::OPEN_STATUSES)
                ->get()
                ->each(fn (Loan $loan) => $this->close($loan, $condition));

            $packageLoan->update(['status' => 'RETURNED', 'returned_at' => now()]);
        });

        $packageLoan->load(['package', 'loans.copy.game', 'user', 'pickupLocation']);
        $this->notify($packageLoan, $actor);

        return $packageLoan;
    }

    private function close(Loan $loan, string $condition): void
    {
        $loan->update([
            'status' => 'RETURNED',
            'returned_at' => now(),
            'return_condition' => $condition,
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

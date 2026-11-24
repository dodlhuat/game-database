<?php

namespace App\Services;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\Reservation;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\LoanCancelledNoShow;
use App\Notifications\ReservationAvailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * A pickup-event booking that was never collected: the loan is cancelled,
 * the fee goes back exactly where it came from (bonus stays bonus, with its
 * expiry), the deposit is released and the copies are free again.
 */
class LoanCancellationService
{
    public function __construct(private TokenWallet $wallet) {}

    public function cancelLoan(Loan $loan): Loan
    {
        $games = DB::transaction(function () use ($loan) {
            /** @var Loan $locked */
            $locked = Loan::with(['copy.game', 'user'])->lockForUpdate()->findOrFail($loan->id);
            $this->assertCancellable($locked->isAwaitingPickup() && $locked->package_loan_id === null);

            /** @var User $user */
            $user = $locked->user;
            $title = $locked->copy->game->title ?? 'Spiel';
            $this->refund($user, $locked->spend_receipt ?? [], $locked->id, "Erstattung (nicht abgeholt): {$title}");
            $this->close($locked);

            return collect([$locked->copy?->game])->filter();
        });

        $loan->refresh();
        $this->notify($loan->user, $loan, $games);

        return $loan;
    }

    public function cancelPackage(PackageLoan $packageLoan): PackageLoan
    {
        $games = DB::transaction(function () use ($packageLoan) {
            /** @var PackageLoan $locked */
            $locked = PackageLoan::with(['loans.copy.game', 'user', 'package'])->lockForUpdate()->findOrFail($packageLoan->id);
            $this->assertCancellable($locked->isAwaitingPickup());

            /** @var User $user */
            $user = $locked->user;
            $name = $locked->package->name ?? 'Paket';
            $this->refund($user, $locked->spend_receipt ?? [], null, "Erstattung (nicht abgeholt): Paket {$name}");

            $locked->loans->each(fn (Loan $loan) => $this->close($loan));
            $locked->update(['status' => 'CANCELLED', 'cancelled_at' => now()]);

            return $locked->loans->map(fn (Loan $l) => $l->copy?->game)->filter()->values();
        });

        $packageLoan->refresh();
        $this->notify($packageLoan->user, $packageLoan, $games);

        return $packageLoan;
    }

    private function assertCancellable(bool $ok): void
    {
        if (! $ok) {
            throw new HttpException(422, 'Nur Buchungen für einen Abholtermin, die noch nicht ausgegeben wurden, können storniert werden.');
        }
    }

    /** @param  list<array{lot_id: int|null, amount: int}>  $receipt */
    private function refund(User $user, array $receipt, ?int $loanId, string $description): void
    {
        $restored = $this->wallet->restore($user, $receipt);
        if ($restored > 0) {
            TokenTransaction::create([
                'user_id' => $user->id,
                'loan_id' => $loanId,
                'type' => 'REFUND',
                'amount' => $restored,
                'description' => $description,
            ]);
        }
    }

    /** Releases the deposit and frees the copy. */
    private function close(Loan $loan): void
    {
        /** @var User $user */
        $user = $loan->user;
        if ($loan->deposit_tokens > 0) {
            User::whereKey($user->id)->decrement('tokens_blocked', $loan->deposit_tokens);
            TokenTransaction::create([
                'user_id' => $user->id,
                'loan_id' => $loan->id,
                'type' => 'DEPOSIT_RELEASE',
                'amount' => $loan->deposit_tokens,
                'description' => 'Kaution freigegeben (nicht abgeholt): '.($loan->copy->game->title ?? 'Spiel'),
            ]);
        }

        /** @var Copy $copy */
        $copy = $loan->copy;
        if ($copy->borrow_count > 0) {
            $copy->decrement('borrow_count');
        }

        $loan->update(['status' => 'CANCELLED', 'cancelled_at' => now()]);
    }

    /**
     * The member hears that the booking is gone; whoever waits first in line
     * for one of the games hears that it is free again.
     *
     * @param  Collection<int, Game>  $games
     */
    private function notify(?User $member, Loan|PackageLoan $booking, Collection $games): void
    {
        try {
            $member?->notify(new LoanCancelledNoShow($booking));

            foreach ($games as $game) {
                $reservation = Reservation::where('game_id', $game->id)->orderBy('position')->first();
                if ($reservation?->user) {
                    $reservation->update(['notified_at' => now()]);
                    $reservation->user->notify(new ReservationAvailable($game));
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}

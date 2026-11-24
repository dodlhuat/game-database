<?php

namespace App\Notifications\Concerns;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\Package;
use App\Models\PackageLoan;

/** {game} for a mail: the game title, or the package with its games. Already HTML-escaped. */
trait DescribesLoanItem
{
    protected function itemTitle(Loan|PackageLoan $loan): string
    {
        if ($loan instanceof PackageLoan) {
            /** @var Package $package */
            $package = $loan->package;
            $games = $loan->loans
                ->map(fn (Loan $l) => $l->copy?->game?->title)
                ->filter()
                ->implode(', ');

            return e('Paket „'.$package->name.'“'.($games !== '' ? " ({$games})" : ''));
        }

        /** @var Copy $copy */
        $copy = $loan->copy;
        /** @var Game $game */
        $game = $copy->game;

        return e($game->title);
    }

    protected function conditionLabel(?string $condition): string
    {
        return match ($condition) {
            'GOOD' => 'Gut',
            'WORN' => 'Abgenutzt',
            'DAMAGED' => 'Beschädigt',
            default => '—',
        };
    }
}

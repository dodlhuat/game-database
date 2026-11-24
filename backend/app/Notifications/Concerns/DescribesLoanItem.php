<?php

namespace App\Notifications\Concerns;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\PickupLocation;

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

    /** Where the game (or package) actually came back — falls back to the pickup place. */
    protected function returnPlaceOf(Loan|PackageLoan $loan): ?PickupLocation
    {
        if ($loan instanceof PackageLoan) {
            return $loan->loans->first()?->returnPlace() ?? $loan->pickupLocation;
        }

        return $loan->returnPlace();
    }

    /** Pickup date — with the time span when it is a personal pickup event. */
    protected function pickupWhen(Loan|PackageLoan $loan): string
    {
        $event = $loan->pickupEvent;
        if ($event) {
            $span = substr($event->time ?? '', 0, 5).($event->end_time ? '–'.substr($event->end_time, 0, 5) : '');

            return $event->date->format('d.m.Y').($span !== '' ? ", {$span} Uhr" : '');
        }

        return $loan->start_date?->format('d.m.Y') ?? '';
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

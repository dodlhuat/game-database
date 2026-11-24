<?php

namespace App\Console\Commands;

use App\Models\Copy;
use App\Models\User;
use App\Notifications\PickupDigestAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendPickupDigest extends Command
{
    protected $signature = 'loans:send-pickup-digest';

    protected $description = 'Mail the admins which returned games wait at which pickup location';

    public function handle(): int
    {
        $copies = Copy::where('condition', 'REVIEW')
            ->with(['game', 'lastReturnedLoan.user', 'lastReturnedLoan.pickupLocation', 'lastReturnedLoan.returnLocation'])
            ->get();

        if ($copies->isEmpty()) {
            $this->info('Nothing waiting for pickup.');

            return self::SUCCESS;
        }

        $admins = User::where('role', 'ADMIN')->get();
        Notification::send($admins, new PickupDigestAdmin($copies->count(), $this->renderList($copies)));

        $this->info("Pickup digest sent to {$admins->count()} admins ({$copies->count()} copies).");

        return self::SUCCESS;
    }

    /** @param  Collection<int, Copy>  $copies */
    private function renderList(Collection $copies): string
    {
        return $copies
            ->groupBy(fn (Copy $c) => $c->lastReturnedLoan?->returnPlace()->name ?? 'Ohne Abholort')
            ->sortKeys()
            ->map(function (Collection $group, string $location) {
                $items = $group
                    ->sortBy(fn (Copy $c) => $c->lastReturnedLoan?->returned_at)
                    ->map(function (Copy $c) {
                        $loan = $c->lastReturnedLoan;
                        $since = $loan?->returned_at?->format('d.m.Y') ?? '—';
                        $who = $loan?->user ? ' · '.e($loan->user->name) : '';

                        return '<li>'.e($c->game->title ?? '—').' ('.e($c->qr_code ?? '—').') — seit '.$since.$who.'</li>';
                    })
                    ->implode('');

                return '<p><strong>'.e($location).'</strong> ('.$group->count().')</p><ul>'.$items.'</ul>';
            })
            ->implode('');
    }
}

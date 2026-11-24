<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PickupLocationResource;
use App\Models\Copy;
use App\Models\Event;
use App\Models\Game;
use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What to bring to a personal pickup event and who gets what: one booking
 * per single game or per package, each ticked off when handed out.
 */
class PickupEventController extends Controller
{
    public function show(Event $event): JsonResponse
    {
        if (! $event->isPickup()) {
            return response()->json(['message' => 'Dieses Event ist kein Abholtermin.'], 404);
        }

        $singles = $event->loans()
            ->whereIn('status', Loan::OPEN_STATUSES)
            ->with(['user', 'copy.game'])
            ->get()
            ->map(fn (Loan $loan) => [
                'type' => 'loan',
                'id' => $loan->id,
                'member' => $this->member($loan->user),
                'package_name' => null,
                'handed_out_at' => $loan->handed_out_at?->toIso8601String(),
                'items' => [$this->item($loan)],
            ]);

        $packages = $event->packageLoans()
            ->where('status', 'ACTIVE')
            ->with(['user', 'package', 'loans.copy.game'])
            ->get()
            ->map(fn (PackageLoan $packageLoan) => [
                'type' => 'package',
                'id' => $packageLoan->id,
                'member' => $this->member($packageLoan->user),
                'package_name' => $packageLoan->package->name ?? null,
                'handed_out_at' => $packageLoan->handed_out_at?->toIso8601String(),
                'items' => $packageLoan->loans->map(fn (Loan $loan) => $this->item($loan))->values()->all(),
            ]);

        $bookings = $singles->concat($packages)
            ->sortBy(fn (array $b) => mb_strtolower($b['member']['name'] ?? ''))
            ->values();

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->date->format('Y-m-d'),
                'time' => $event->time ? substr($event->time, 0, 5) : null,
                'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : null,
                'max_loans' => $event->max_loans,
                'location' => $event->pickupLocation ? new PickupLocationResource($event->pickupLocation) : null,
            ],
            'bookings' => $bookings,
        ]);
    }

    /** Ticks a single-game booking off (or back on, after a mis-tap). */
    public function handOutLoan(Request $request, Loan $loan): JsonResponse
    {
        $request->validate(['handed_out' => ['required', 'boolean']]);

        if ($loan->pickup_event_id === null || $loan->package_loan_id !== null
            || ! in_array($loan->status, Loan::OPEN_STATUSES, true)) {
            return response()->json(['message' => 'Diese Ausleihe gehört zu keinem offenen Abholtermin.'], 422);
        }

        $loan->update(['handed_out_at' => $request->boolean('handed_out') ? now() : null]);

        return response()->json(['handed_out_at' => $loan->handed_out_at?->toIso8601String()]);
    }

    /** Ticks a package off — every game in it goes out together. */
    public function handOutPackage(Request $request, PackageLoan $packageLoan): JsonResponse
    {
        $request->validate(['handed_out' => ['required', 'boolean']]);

        if ($packageLoan->pickup_event_id === null || $packageLoan->status !== 'ACTIVE') {
            return response()->json(['message' => 'Diese Paket-Ausleihe gehört zu keinem offenen Abholtermin.'], 422);
        }

        $at = $request->boolean('handed_out') ? now() : null;
        DB::transaction(function () use ($packageLoan, $at) {
            $packageLoan->update(['handed_out_at' => $at]);
            $packageLoan->loans()->update(['handed_out_at' => $at]);
        });

        return response()->json(['handed_out_at' => $at?->toIso8601String()]);
    }

    /** @return array{id: int, name: string, email: string}|null */
    private function member(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : null;
    }

    /** @return array{loan_id: int, game_title: string|null, qr_code: string|null} */
    private function item(Loan $loan): array
    {
        /** @var Copy|null $copy */
        $copy = $loan->copy;
        /** @var Game|null $game */
        $game = $copy?->game;

        return [
            'loan_id' => $loan->id,
            'game_title' => $game?->title,
            'qr_code' => $copy?->qr_code,
        ];
    }
}

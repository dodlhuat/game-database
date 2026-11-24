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
use App\Services\LoanCancellationService;
use App\Services\LoanReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'returns' => $this->returnsAt($event),
        ]);
    }

    /**
     * A member hands a game back at the event: scanning one copy books the
     * return here — for a package, the whole package.
     */
    public function returnAtEvent(Request $request, Event $event, LoanReturnService $returns): JsonResponse
    {
        $request->validate([
            'qr_code' => ['required', 'string', 'max:64'],
            'return_condition' => ['nullable', Rule::in(['GOOD', 'WORN', 'DAMAGED'])],
        ]);

        if (! $event->isPickup() || ! $event->pickupLocation) {
            return response()->json(['message' => 'Dieses Event ist kein Abholtermin.'], 404);
        }

        $code = strtoupper(trim($request->string('qr_code')->toString()));
        /** @var Copy|null $copy */
        $copy = Copy::whereRaw('UPPER(qr_code) = ?', [$code])->first();
        /** @var Loan|null $loan */
        $loan = $copy?->loans()->whereIn('status', Loan::OPEN_STATUSES)->with(['packageLoan', 'copy.game'])->first();

        if (! $loan) {
            return response()->json(['message' => "Zu Code {$code} gibt es keine offene Ausleihe.", 'reason' => 'not_found'], 404);
        }
        if ($loan->isAwaitingPickup()) {
            return response()->json(['message' => LoanReturnService::AWAITING_PICKUP_MESSAGE, 'reason' => 'awaiting_pickup'], 422);
        }

        /** @var User $admin */
        $admin = $request->user();
        $condition = $request->string('return_condition', 'GOOD')->toString();
        $place = $event->pickupLocation;

        if ($loan->packageLoan) {
            $returns->returnPackage($loan->packageLoan, $condition, $admin, $place, $event);
        } else {
            $returns->returnLoan($loan, $condition, $admin, $place, $event);
        }

        return response()->json([
            'message' => ($loan->packageLoan ? 'Paket' : ($loan->copy->game->title ?? 'Spiel')).' zurückgenommen.',
            'returns' => $this->returnsAt($event),
        ]);
    }

    /** @return list<array{loan_id: int, game_title: string|null, member: string|null, condition: string|null}> */
    private function returnsAt(Event $event): array
    {
        return array_values(Loan::where('return_event_id', $event->id)
            ->with(['copy.game', 'user'])
            ->orderByDesc('returned_at')
            ->get()
            ->map(fn (Loan $loan) => [
                'loan_id' => $loan->id,
                'game_title' => $loan->copy->game->title ?? null,
                'member' => $loan->user->name ?? null,
                'condition' => $loan->return_condition,
            ])
            ->all());
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

    /** No-show: cancels and refunds a single-game booking. */
    public function cancelLoan(Loan $loan, LoanCancellationService $cancellations): JsonResponse
    {
        $cancellations->cancelLoan($loan);

        return response()->json(['message' => 'Ausleihe storniert, Token erstattet.']);
    }

    /** No-show: cancels and refunds a package booking with all its games. */
    public function cancelPackage(PackageLoan $packageLoan, LoanCancellationService $cancellations): JsonResponse
    {
        $cancellations->cancelPackage($packageLoan);

        return response()->json(['message' => 'Paket-Ausleihe storniert, Token erstattet.']);
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

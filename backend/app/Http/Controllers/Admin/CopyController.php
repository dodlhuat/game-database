<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyRequest;
use App\Http\Resources\CopyResource;
use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\DepositForfeited;
use App\Notifications\DepositReleased;
use App\Notifications\ReservationAvailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CopyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $copies = Copy::with(['game', 'activeLoans', 'lastReturnedLoan.user'])
            ->when($request->game_id, fn ($q, $id) => $q->where('game_id', $id))
            ->when($request->condition, fn ($q, $c) => $q->where('condition', $c))
            ->when(
                $request->condition === 'REVIEW',
                fn ($q) => $q
                    ->addSelect(['last_returned_at' => Loan::select('returned_at')
                        ->whereColumn('copy_id', 'copies.id')
                        ->where('status', 'RETURNED')
                        ->latest('returned_at')
                        ->limit(1),
                    ])
                    ->orderByRaw('last_returned_at IS NULL')
                    ->orderBy('last_returned_at'),
                fn ($q) => $q->orderBy('game_id')
            )
            ->paginate(min(max((int) $request->per_page ?: 50, 1), 200));

        return CopyResource::collection($copies);
    }

    public function store(CopyRequest $request): CopyResource
    {
        $data = $request->validated();
        $data['qr_code'] = $data['qr_code'] ?? strtoupper(Str::random(8));

        $copy = Copy::create($data);
        $copy->load(['game', 'activeLoans']);

        return new CopyResource($copy);
    }

    public function show(Copy $copy): CopyResource
    {
        return new CopyResource($copy->load(['game', 'activeLoans']));
    }

    public function lookup(Request $request): JsonResponse|CopyResource
    {
        $request->validate([
            'qr_code' => ['required', 'string'],
        ]);

        $copy = Copy::with(['game', 'activeLoans', 'lastReturnedLoan.user'])
            ->where('qr_code', strtoupper(trim((string) $request->qr_code)))
            ->first();

        if (! $copy) {
            return response()->json(['message' => 'Kein Spiel mit diesem Code gefunden.'], 404);
        }

        return new CopyResource($copy);
    }

    public function update(CopyRequest $request, Copy $copy): CopyResource
    {
        $copy->update($request->validated());

        return new CopyResource($copy->load(['game', 'activeLoans']));
    }

    public function destroy(Copy $copy): JsonResponse
    {
        if ($copy->activeLoans()->exists()) {
            return response()->json(['message' => 'Kopie ist aktuell ausgeliehen.'], 422);
        }

        $copy->delete();

        return response()->json(['message' => 'Kopie gelöscht.']);
    }

    public function approve(Request $request, Copy $copy): JsonResponse
    {
        $request->validate([
            'condition' => ['nullable', 'in:NEW,VERY_GOOD,GOOD,WORN'],
        ]);

        if ($copy->condition !== 'REVIEW') {
            return response()->json(['message' => 'Kopie ist nicht im Status "Überprüfen".'], 422);
        }

        $setting = LoanSetting::instance();

        ['depositLoan' => $depositLoan, 'reservationUser' => $reservationUser, 'reservationGame' => $reservationGame] = DB::transaction(function () use ($copy, $setting, $request) {
            $newCondition = $request->condition ?? $copy->resolveConditionFromBorrowCount($setting);
            $copy->update(['condition' => $newCondition]);

            $loan = $copy->loans()
                ->where('status', 'RETURNED')
                ->latest('returned_at')
                ->with(['user', 'copy.game'])
                ->first();

            $depositLoan = null;

            if ($loan && $loan->deposit_tokens > 0) {
                /** @var User $loanUser */
                $loanUser = $loan->user;
                /** @var Copy $loanCopy */
                $loanCopy = $loan->copy;
                /** @var Game $loanGame */
                $loanGame = $loanCopy->game;
                $loanUser->decrement('tokens_blocked', $loan->deposit_tokens);
                TokenTransaction::create([
                    'user_id' => $loan->user_id,
                    'loan_id' => $loan->id,
                    'type' => 'DEPOSIT_RELEASE',
                    'amount' => $loan->deposit_tokens,
                    'description' => "Kaution freigegeben: {$loanGame->title}",
                ]);
                $depositLoan = $loan;
            }

            $reservation = Reservation::where('game_id', $copy->game_id)
                ->orderBy('position')
                ->first();

            $reservationUser = null;
            $reservationGame = null;

            if ($reservation) {
                /** @var User $resUser */
                $resUser = $reservation->user;
                $reservationUser = $resUser;
                $reservationGame = $copy->game;
                $reservation->update(['notified_at' => now()]);
            }

            return compact('depositLoan', 'reservationUser', 'reservationGame');
        });

        if ($depositLoan) {
            /** @var User $depositUser */
            $depositUser = $depositLoan->user;
            $depositUser->notify(new DepositReleased($depositLoan));
        }

        if ($reservationUser && $reservationGame) {
            $reservationUser->notify(new ReservationAvailable($reservationGame));
        }

        return response()->json([
            'message' => 'Kopie freigegeben.',
            'copy' => new CopyResource($copy->load(['game', 'activeLoans'])),
        ]);
    }

    public function markDamaged(Request $request, Copy $copy): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($copy->condition !== 'REVIEW') {
            return response()->json(['message' => 'Kopie ist nicht im Status "Überprüfen".'], 422);
        }

        $notes = $request->notes;

        ['depositLoan' => $depositLoan] = DB::transaction(function () use ($copy, $notes) {
            $copy->update([
                'condition' => 'DAMAGED',
                'notes' => $notes ?? $copy->notes,
            ]);

            $loan = $copy->loans()
                ->where('status', 'RETURNED')
                ->latest('returned_at')
                ->with(['user', 'copy.game'])
                ->first();

            $depositLoan = null;

            if ($loan && $loan->deposit_tokens > 0) {
                /** @var User $loanUser */
                $loanUser = $loan->user;
                /** @var Copy $loanCopy */
                $loanCopy = $loan->copy;
                /** @var Game $loanGame */
                $loanGame = $loanCopy->game;
                $loanUser->decrement('tokens_blocked', $loan->deposit_tokens);
                TokenTransaction::create([
                    'user_id' => $loan->user_id,
                    'loan_id' => $loan->id,
                    'type' => 'DEPOSIT_FORFEIT',
                    'amount' => -$loan->deposit_tokens,
                    'description' => "Kaution einbehalten (Beschädigung): {$loanGame->title}",
                ]);
                $depositLoan = $loan;
            }

            return compact('depositLoan');
        });

        if ($depositLoan) {
            /** @var User $depositUser */
            $depositUser = $depositLoan->user;
            $depositUser->notify(new DepositForfeited($depositLoan, $notes));
        }

        return response()->json([
            'message' => 'Kopie als beschädigt markiert.',
            'copy' => new CopyResource($copy->load(['game', 'activeLoans'])),
        ]);
    }
}

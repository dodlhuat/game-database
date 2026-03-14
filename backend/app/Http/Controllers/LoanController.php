<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\ReturnLoanRequest;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Notifications\ReservationAvailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $loans = $request->user()->loans()
            ->with(['copy.game', 'extensions'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return LoanResource::collection($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse|LoanResource
    {
        $copy = Copy::findOrFail($request->copy_id);

        if ($copy->condition === 'LOCKED') {
            return response()->json(['message' => 'Diese Kopie ist nicht ausleihbar.'], 422);
        }

        $isAvailable = $copy->activeLoans()->doesntExist();
        if (!$isAvailable) {
            return response()->json(['message' => 'Diese Kopie ist gerade ausgeliehen.'], 422);
        }

        $loan = Loan::create([
            'copy_id'    => $copy->id,
            'user_id'    => $request->user()->id,
            'start_date' => $request->start_date,
            'due_date'   => $request->due_date,
            'status'     => 'ACTIVE',
        ]);

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }

    public function show(Request $request, Loan $loan): JsonResponse|LoanResource
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }

    public function return(ReturnLoanRequest $request, Loan $loan): JsonResponse|LoanResource
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        if (!in_array($loan->status, ['ACTIVE', 'EXTENDED', 'OVERDUE'])) {
            return response()->json(['message' => 'Ausleihe ist bereits zurückgegeben.'], 422);
        }

        $loan->update([
            'status'           => 'RETURNED',
            'returned_at'      => now(),
            'return_condition' => $request->return_condition,
        ]);

        // Kopienzustand aktualisieren falls beschädigt
        if ($request->return_condition !== 'GOOD') {
            $loan->copy->update(['condition' => $request->return_condition]);
        }

        // Erste Person in der Reservierungswarteschlange benachrichtigen
        $reservation = Reservation::where('game_id', $loan->copy->game_id)
            ->orderBy('position')
            ->first();

        if ($reservation) {
            $reservation->user->notify(new ReservationAvailable($loan->copy->game));
            $reservation->update(['notified_at' => now()]);
        }

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\ReturnLoanRequest;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Notifications\ReservationAvailable;
use Carbon\Carbon;
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
        $user = $request->user();

        if (!$user->isAdmin()) {
            if (!$user->isMember()) {
                return response()->json([
                    'message' => 'Eine aktive Mitgliedschaft ist erforderlich um Spiele auszuleihen.',
                    'reason'  => 'membership_required',
                ], 403);
            }
            if (!$user->hasEnoughTokens(2)) {
                return response()->json([
                    'message' => 'Nicht genug Token. Ausleihen kostet 2 Token.',
                    'reason'  => 'insufficient_tokens',
                ], 402);
            }
        }

        $copy = Copy::findOrFail($request->copy_id);

        if ($copy->condition === 'LOCKED') {
            return response()->json(['message' => 'Diese Kopie ist nicht ausleihbar.'], 422);
        }

        $isAvailable = $copy->activeLoans()->doesntExist();
        if (!$isAvailable) {
            return response()->json(['message' => 'Diese Kopie ist gerade ausgeliehen.'], 422);
        }

        $alreadyBorrowed = $user->loans()
            ->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])
            ->whereHas('copy', fn ($q) => $q->where('game_id', $copy->game_id))
            ->exists();

        if ($alreadyBorrowed) {
            return response()->json(['message' => 'Du hast dieses Spiel bereits ausgeliehen.'], 422);
        }

        $setting   = LoanSetting::instance();
        $startDate = $this->calcNextAppointment($setting);
        $dueDate   = $startDate->copy()->addWeeks($setting->loan_duration_weeks);

        $loan = Loan::create([
            'copy_id'    => $copy->id,
            'user_id'    => $user->id,
            'start_date' => $startDate->toDateString(),
            'due_date'   => $dueDate->toDateString(),
            'status'     => 'ACTIVE',
        ]);

        if (!$user->isAdmin()) {
            $user->decrement('tokens', 2);
        }

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }

    private function calcNextAppointment(LoanSetting $setting): Carbon
    {
        $today     = Carbon::today();
        $deadline  = $today->copy()->addDays($setting->grace_days);
        $startDate = Carbon::parse($setting->start_date);

        if ($setting->interval_days <= 0) {
            return $deadline->copy()->addDay();
        }

        $daysSinceStart = (int) $startDate->diffInDays($deadline, false);
        $n = $daysSinceStart < 0 ? 0 : (int) floor($daysSinceStart / $setting->interval_days) + 1;

        return $startDate->copy()->addDays($n * $setting->interval_days);
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

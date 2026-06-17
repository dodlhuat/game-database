<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\ReturnLoanRequest;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\TokenTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $loans = $user->loans()
            ->with(['copy.game', 'extensions'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return LoanResource::collection($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse|LoanResource
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isMember()) {
            return response()->json([
                'message' => 'Eine aktive Mitgliedschaft ist erforderlich um Spiele auszuleihen.',
                'reason' => 'membership_required',
            ], 403);
        }

        $setting = LoanSetting::instance();

        // Vorzeitige Token-Prüfung außerhalb der Transaktion, damit wir früh abbrechen können
        if (! $user->isAdmin()) {
            $copy = Copy::with('game')->findOrFail($request->copy_id);
            /** @var Game $game */
            $game = $copy->game;
            $loanCost = $setting->loan_cost;
            $deposit = $copy->calculateDeposit($game, $setting);

            if (! $user->hasEnoughFreeTokens($loanCost + $deposit)) {
                return response()->json([
                    'message' => "Nicht genug Token. Ausleihen kostet {$loanCost} Token + {$deposit} Token Kaution.",
                    'reason' => 'insufficient_tokens',
                    'borrow_cost' => $loanCost,
                    'deposit' => $deposit,
                ], 402);
            }
        }

        $loan = DB::transaction(function () use ($request, $user, $setting) {
            // Kopie mit Lock holen, damit kein zweiter Request dieselbe Kopie gleichzeitig verleiht
            /** @var Copy $copy */
            $copy = Copy::with('game')->lockForUpdate()->findOrFail($request->copy_id);
            /** @var Game $game */
            $game = $copy->game;

            if (in_array($copy->condition, ['LOCKED', 'REVIEW', 'DAMAGED'])) {
                abort(422, 'Diese Kopie ist nicht ausleihbar.');
            }

            if ($copy->activeLoans()->exists()) {
                abort(422, 'Diese Kopie ist gerade ausgeliehen.');
            }

            $alreadyBorrowed = $user->loans()
                ->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])
                ->whereHas('copy', fn ($q) => $q->where('game_id', $copy->game_id))
                ->exists();

            if ($alreadyBorrowed) {
                abort(422, 'Du hast dieses Spiel bereits ausgeliehen.');
            }

            $loanCost = $user->isAdmin() ? 0 : $setting->loan_cost;
            $deposit = $user->isAdmin() ? 0 : $copy->calculateDeposit($game, $setting);

            $startDate = $this->calcNextAppointment($setting);
            $dueDate = $startDate->copy()->addWeeks($setting->loan_duration_weeks);

            $loan = Loan::create([
                'copy_id' => $copy->id,
                'user_id' => $user->id,
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'ACTIVE',
                'deposit_tokens' => $deposit,
            ]);

            if (! $user->isAdmin()) {
                $user->decrement('tokens', $loanCost);
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'loan_id' => $loan->id,
                    'type' => 'BORROW',
                    'amount' => -$loanCost,
                    'description' => "Leihgebühr: {$game->title}",
                ]);

                if ($deposit > 0) {
                    $user->increment('tokens_blocked', $deposit);
                    TokenTransaction::create([
                        'user_id' => $user->id,
                        'loan_id' => $loan->id,
                        'type' => 'DEPOSIT_BLOCK',
                        'amount' => -$deposit,
                        'description' => "Kaution blockiert: {$game->title}",
                    ]);
                }
            }

            $copy->increment('borrow_count');

            return $loan;
        });

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }

    private function calcNextAppointment(LoanSetting $setting): Carbon
    {
        $today = Carbon::today();
        $deadline = $today->copy()->addDays($setting->grace_days);
        $startDate = Carbon::parse($setting->start_date);

        if ($setting->interval_days <= 0) {
            return $deadline->copy()->addDay();
        }

        $daysSinceStart = (int) $startDate->diffInDays($deadline, false);
        $n = $daysSinceStart < 0 ? 0 : (int) floor($daysSinceStart / $setting->interval_days) + 1;

        return $startDate->copy()->addDays($n * $setting->interval_days);
    }

    public function show(Request $request, Loan $loan): LoanResource
    {
        $this->authorize('view', $loan);

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }

    public function return(ReturnLoanRequest $request, Loan $loan): JsonResponse|LoanResource
    {
        $this->authorize('return', $loan);

        if (! in_array($loan->status, ['ACTIVE', 'EXTENDED', 'OVERDUE'])) {
            return response()->json(['message' => 'Ausleihe ist bereits zurückgegeben.'], 422);
        }

        $loan->update([
            'status' => 'RETURNED',
            'returned_at' => now(),
            'return_condition' => $request->return_condition,
        ]);

        $loan->copy->update(['condition' => 'REVIEW']);

        $loan->load(['copy.game', 'extensions']);

        return new LoanResource($loan);
    }
}

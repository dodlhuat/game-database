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
use App\Notifications\LoanConfirmation;
use App\Services\LoanReturnService;
use App\Services\PickupScheduler;
use App\Services\TokenWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function __construct(
        private TokenWallet $wallet,
        private LoanReturnService $returns,
        private PickupScheduler $pickups,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $loans = $user->loans()
            ->with(['copy.game.reservations', 'extensions'])
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
        $pickup = $this->pickups->resolve($request->input('pickup_location_id'), $request->input('pickup_event_id'), $user, $setting);
        if (! $pickup) {
            return $this->invalidLocationResponse();
        }
        ['location' => $location, 'event' => $event] = $pickup;

        // Vorzeitige Token-Prüfung außerhalb der Transaktion, damit wir früh abbrechen können
        if (! $user->isAdmin()) {
            /** @var Copy $copy */
            $copy = Copy::with('game')->findOrFail($request->copy_id);
            /** @var Game $game */
            $game = $copy->game;
            $loanCost = $setting->costFor($game->size_category);
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

        $loan = DB::transaction(function () use ($request, $user, $setting, $location, $event) {
            if ($event) {
                $this->pickups->claimSpot($event);
            }

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

            $loanCost = $user->isAdmin() ? 0 : $setting->costFor($game->size_category);
            $deposit = $user->isAdmin() ? 0 : $copy->calculateDeposit($game, $setting);

            $startDate = $this->pickups->startDate($event, $setting);
            $dueDate = $startDate->copy()->addWeeks($setting->loan_duration_weeks);

            $loan = Loan::create([
                'copy_id' => $copy->id,
                'user_id' => $user->id,
                'pickup_location_id' => $location->id,
                'pickup_event_id' => $event?->id,
                // Regular loans are out right away; event loans wait for the hand-out
                'handed_out_at' => $event ? null : now(),
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'ACTIVE',
                'deposit_tokens' => $deposit,
            ]);

            if (! $user->isAdmin()) {
                // Kept so a cancelled pickup can refund every token to where it came from
                $loan->update(['spend_receipt' => $this->wallet->spend($user, $loanCost)]);
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

        $loan->load(['copy.game', 'extensions', 'pickupLocation', 'pickupEvent']);

        LoanConfirmation::sendSafely($user, $loan);

        return new LoanResource($loan);
    }

    private function invalidLocationResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Bitte wähle einen gültigen Abholort.',
            'reason' => 'invalid_pickup_location',
            'errors' => ['pickup_location_id' => ['Bitte wähle einen gültigen Abholort.']],
        ], 422);
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

        if (! in_array($loan->status, Loan::OPEN_STATUSES)) {
            return response()->json(['message' => 'Ausleihe ist bereits zurückgegeben.'], 422);
        }

        if ($loan->isAwaitingPickup()) {
            return response()->json(['message' => LoanReturnService::AWAITING_PICKUP_MESSAGE, 'reason' => 'awaiting_pickup'], 422);
        }

        if ($loan->package_loan_id !== null) {
            return response()->json([
                'message' => 'Dieses Spiel gehört zu einem Paket. Bitte gib das ganze Paket zurück.',
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $this->returns->returnLoan($loan, $request->string('return_condition')->toString(), $user);

        return new LoanResource($loan->load(['copy.game', 'extensions']));
    }
}

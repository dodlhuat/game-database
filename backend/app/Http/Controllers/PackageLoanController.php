<?php

namespace App\Http\Controllers;

use App\Http\Resources\PackageLoanResource;
use App\Http\Resources\ReturnPlacesResource;
use App\Models\Copy;
use App\Models\Game;
use App\Models\LoanSetting;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\LoanConfirmation;
use App\Services\LoanReturnService;
use App\Services\PickupScheduler;
use App\Services\ReturnPlaces;
use App\Services\TokenWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PackageLoanController extends Controller
{
    public function __construct(
        private TokenWallet $wallet,
        private PickupScheduler $pickups,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $loans = $user->packageLoans()
            ->with(['package', 'loans.copy.game'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return PackageLoanResource::collection($loans);
    }

    public function store(Request $request): JsonResponse|PackageLoanResource
    {
        $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            // Optional only while a single location is active — then it is implied.
            'pickup_location_id' => ['nullable', 'integer'],
            // Alternative to a location: a personal pickup event (brings place and date)
            'pickup_event_id' => ['nullable', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $setting = LoanSetting::instance();
        $pickup = $this->pickups->resolve($request->input('pickup_location_id'), $request->input('pickup_event_id'), $user, $setting);
        if (! $pickup) {
            return response()->json([
                'message' => 'Bitte wähle einen gültigen Abholort.',
                'reason' => 'invalid_pickup_location',
                'errors' => ['pickup_location_id' => ['Bitte wähle einen gültigen Abholort.']],
            ], 422);
        }

        ['location' => $location, 'event' => $event] = $pickup;
        /** @var Package $package */
        $package = Package::with('games')->findOrFail($request->package_id);

        if (! $package->is_active) {
            return response()->json(['message' => 'Paket nicht verfügbar.'], 422);
        }

        if ($package->games->isEmpty()) {
            return response()->json(['message' => 'Dieses Paket enthält keine Spiele.'], 422);
        }

        if (! $user->isAdmin()) {
            if (! $user->isMember()) {
                return response()->json([
                    'message' => 'Eine aktive Mitgliedschaft ist erforderlich um Pakete auszuleihen.',
                    'reason' => 'membership_required',
                ], 403);
            }
        }

        // Find one available copy per game and calculate total deposit
        $selectedCopies = [];
        foreach ($package->games as $game) {
            $copy = Copy::with('game')
                ->where('game_id', $game->id)
                ->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])
                ->whereDoesntHave('activeLoans')
                ->first();

            if (! $copy) {
                return response()->json([
                    'message' => 'Das Paket ist nicht vollständig verfügbar. "'.$game->title.'" ist derzeit ausgeliehen.',
                    'reason' => 'package_unavailable',
                ], 409);
            }

            $selectedCopies[] = $copy;
        }

        if (! $user->isAdmin()) {
            $loanCost = $setting->package_loan_cost;
            $totalDeposit = 0;
            foreach ($selectedCopies as $copy) {
                /** @var Game $copyGame */
                $copyGame = $copy->game;
                $totalDeposit += $copy->calculateDeposit($copyGame, $setting);
            }
            $totalCost = $loanCost + $totalDeposit;

            if (! $user->hasEnoughFreeTokens($totalCost)) {
                return response()->json([
                    'message' => "Nicht genug Token. Paket ausleihen kostet {$loanCost} Token + {$totalDeposit} Token Kaution.",
                    'reason' => 'insufficient_tokens',
                    'borrow_cost' => $loanCost,
                    /** @var int */
                    'deposit' => $totalDeposit,
                ], 402);
            }
        } else {
            $totalDeposit = 0;
        }

        $startDate = $this->pickups->startDate($event, $setting);
        $dueDate = $startDate->copy()->addWeeks($setting->loan_duration_weeks);

        // One transaction, so a full pickup event or a failure halfway leaves nothing behind
        $packageLoan = DB::transaction(function () use ($event, $package, $user, $location, $startDate, $dueDate, $selectedCopies, $setting, $totalDeposit) {
            if ($event) {
                $this->pickups->claimSpot($event);
            }

            $packageLoan = PackageLoan::create([
                'package_id' => $package->id,
                'user_id' => $user->id,
                'pickup_location_id' => $location->id,
                'pickup_event_id' => $event?->id,
                // Regular loans are out right away; event loans wait for the hand-out
                'handed_out_at' => $event ? null : now(),
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'ACTIVE',
            ]);

            foreach ($selectedCopies as $copy) {
                /** @var Game $copyGame */
                $copyGame = $copy->game;
                $deposit = $user->isAdmin() ? 0 : $copy->calculateDeposit($copyGame, $setting);
                $packageLoan->loans()->create([
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
                $copy->increment('borrow_count');
            }

            if (! $user->isAdmin()) {
                $loanCost = $setting->package_loan_cost;

                // Kept so a cancelled pickup can refund every token to where it came from
                $packageLoan->update(['spend_receipt' => $this->wallet->spend($user, $loanCost)]);
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'loan_id' => null,
                    'type' => 'BORROW',
                    'amount' => -$loanCost,
                    'description' => "Leihgebühr: Paket {$package->name}",
                ]);

                if ($totalDeposit > 0) {
                    $user->increment('tokens_blocked', $totalDeposit);
                    TokenTransaction::create([
                        'user_id' => $user->id,
                        'loan_id' => null,
                        'type' => 'DEPOSIT_BLOCK',
                        'amount' => -$totalDeposit,
                        'description' => "Kaution blockiert: Paket {$package->name}",
                    ]);
                }
            }

            return $packageLoan;
        });

        $packageLoan->load(['package', 'loans.copy.game', 'pickupLocation', 'pickupEvent']);

        LoanConfirmation::sendSafely($user, $packageLoan);

        return new PackageLoanResource($packageLoan);
    }

    public function return(Request $request, PackageLoan $packageLoan, LoanReturnService $returns, ReturnPlaces $places): JsonResponse|PackageLoanResource
    {
        /** @var User $user */
        $user = $request->user();
        if ($packageLoan->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        if ($packageLoan->status !== 'ACTIVE') {
            return response()->json(['message' => 'Dieses Paket ist bereits zurückgegeben.'], 422);
        }

        if ($packageLoan->isAwaitingPickup()) {
            return response()->json(['message' => LoanReturnService::AWAITING_PICKUP_MESSAGE, 'reason' => 'awaiting_pickup'], 422);
        }

        $validated = $request->validate([
            'return_condition' => ['required', Rule::in(['GOOD', 'WORN', 'DAMAGED'])],
            'return_location_id' => ['nullable', 'integer'],
            'return_event_id' => ['nullable', 'integer'],
        ]);

        $place = $places->resolve($packageLoan, $user, $request->input('return_location_id'), $request->input('return_event_id'));
        if (! $place) {
            return response()->json([
                'message' => 'Bitte wähle, wo du das Spiel zurückgegeben hast.',
                'reason' => 'invalid_return_place',
                'errors' => ['return_location_id' => ['Bitte wähle, wo du das Spiel zurückgegeben hast.']],
            ], 422);
        }

        $returns->returnPackage($packageLoan, $validated['return_condition'], $user, $place['location'], $place['event']);

        return new PackageLoanResource($packageLoan);
    }

    /** Where this package may be returned — the member picks one when confirming the return. */
    public function returnOptions(Request $request, PackageLoan $packageLoan, ReturnPlaces $places): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($packageLoan->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        return response()->json(ReturnPlacesResource::make($places->optionsFor($packageLoan, $user)));
    }
}

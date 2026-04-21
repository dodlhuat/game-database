<?php

namespace App\Http\Controllers;

use App\Http\Resources\PackageLoanResource;
use App\Models\Copy;
use App\Models\LoanSetting;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\TokenTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PackageLoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $loans = $request->user()->packageLoans()
            ->with(['package', 'loans.copy.game'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return PackageLoanResource::collection($loans);
    }

    public function store(Request $request): JsonResponse|PackageLoanResource
    {
        $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
        ]);

        $user    = $request->user();
        $package = Package::with('games')->findOrFail($request->package_id);
        $setting = LoanSetting::instance();

        if (!$package->is_active) {
            return response()->json(['message' => 'Paket nicht verfügbar.'], 422);
        }

        if ($package->games->isEmpty()) {
            return response()->json(['message' => 'Dieses Paket enthält keine Spiele.'], 422);
        }

        if (!$user->isAdmin()) {
            if (!$user->isMember()) {
                return response()->json([
                    'message' => 'Eine aktive Mitgliedschaft ist erforderlich um Pakete auszuleihen.',
                    'reason'  => 'membership_required',
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

            if (!$copy) {
                return response()->json([
                    'message' => 'Das Paket ist nicht vollständig verfügbar. "' . $game->title . '" ist derzeit ausgeliehen.',
                    'reason'  => 'package_unavailable',
                ], 409);
            }

            $selectedCopies[] = $copy;
        }

        if (!$user->isAdmin()) {
            $loanCost    = $setting->loan_cost;
            $totalDeposit = 0;
            foreach ($selectedCopies as $copy) {
                $totalDeposit += $copy->calculateDeposit($copy->game, $setting);
            }
            $totalCost = $loanCost + $totalDeposit;

            if (!$user->hasEnoughFreeTokens($totalCost)) {
                return response()->json([
                    'message'    => "Nicht genug Token. Paket ausleihen kostet {$loanCost} Token + {$totalDeposit} Token Kaution.",
                    'reason'     => 'insufficient_tokens',
                    'borrow_cost' => $loanCost,
                    'deposit'    => $totalDeposit,
                ], 402);
            }
        } else {
            $totalDeposit = 0;
        }

        $startDate = $this->calcNextAppointment($setting);
        $dueDate   = $startDate->copy()->addWeeks($setting->loan_duration_weeks);

        $packageLoan = PackageLoan::create([
            'package_id' => $package->id,
            'user_id'    => $user->id,
            'start_date' => $startDate->toDateString(),
            'due_date'   => $dueDate->toDateString(),
            'status'     => 'ACTIVE',
        ]);

        foreach ($selectedCopies as $copy) {
            $deposit = $user->isAdmin() ? 0 : $copy->calculateDeposit($copy->game, $setting);
            $packageLoan->loans()->create([
                'copy_id'        => $copy->id,
                'user_id'        => $user->id,
                'start_date'     => $startDate->toDateString(),
                'due_date'       => $dueDate->toDateString(),
                'status'         => 'ACTIVE',
                'deposit_tokens' => $deposit,
            ]);
            $copy->increment('borrow_count');
        }

        if (!$user->isAdmin()) {
            $loanCost = $setting->loan_cost;

            $user->decrement('tokens', $loanCost);
            TokenTransaction::create([
                'user_id'     => $user->id,
                'loan_id'     => null,
                'type'        => 'BORROW',
                'amount'      => -$loanCost,
                'description' => "Leihgebühr: Paket {$package->name}",
            ]);

            if ($totalDeposit > 0) {
                $user->increment('tokens_blocked', $totalDeposit);
                TokenTransaction::create([
                    'user_id'     => $user->id,
                    'loan_id'     => null,
                    'type'        => 'DEPOSIT_BLOCK',
                    'amount'      => -$totalDeposit,
                    'description' => "Kaution blockiert: Paket {$package->name}",
                ]);
            }
        }

        $packageLoan->load(['package', 'loans.copy.game']);

        return new PackageLoanResource($packageLoan);
    }

    public function return(Request $request, PackageLoan $packageLoan): JsonResponse|PackageLoanResource
    {
        if ($packageLoan->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Keine Berechtigung.'], 403);
        }

        if ($packageLoan->status !== 'ACTIVE') {
            return response()->json(['message' => 'Dieses Paket ist bereits zurückgegeben.'], 422);
        }

        // Update each individual loan and set copy to REVIEW
        $packageLoan->loans()
            ->with('copy')
            ->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])
            ->get()
            ->each(function ($loan) {
                $loan->update(['status' => 'RETURNED', 'returned_at' => now()]);
                $loan->copy->update(['condition' => 'REVIEW']);
            });

        $packageLoan->update(['status' => 'RETURNED', 'returned_at' => now()]);

        $packageLoan->load(['package', 'loans.copy.game']);

        return new PackageLoanResource($packageLoan);
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
}

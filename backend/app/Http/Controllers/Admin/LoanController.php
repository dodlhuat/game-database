<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Models\PickupLocation;
use App\Models\User;
use App\Notifications\LoanOverdueReminder;
use App\Services\LoanReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $loans = Loan::with(['copy.game', 'user', 'extensions'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->pickup_location_id, fn ($q, $id) => $q->where('pickup_location_id', $id))
            ->when($request->game_id, fn ($q, $id) => $q->whereHas('copy', fn ($q) => $q->where('game_id', $id))
            )
            ->orderByDesc('created_at')
            ->paginate(25);

        return LoanResource::collection($loans);
    }

    public function markOverdue(Loan $loan): JsonResponse|LoanResource
    {
        if (! in_array($loan->status, ['ACTIVE', 'EXTENDED'])) {
            return response()->json(['message' => 'Ausleihe kann nicht als überfällig markiert werden.'], 422);
        }

        $loan->update(['status' => 'OVERDUE']);

        return new LoanResource($loan->load(['copy.game', 'user', 'extensions']));
    }

    public function sendOverdueReminder(Loan $loan): JsonResponse|LoanResource
    {
        if ($loan->status !== 'OVERDUE') {
            return response()->json(['message' => 'Nur überfällige Ausleihen können erinnert werden.'], 422);
        }

        $loan->load(['copy.game', 'user', 'extensions']);

        /** @var User $loanUser */
        $loanUser = $loan->user;
        $loanUser->notify(new LoanOverdueReminder($loan));

        $loan->update(['overdue_reminder_sent_at' => now()]);

        return new LoanResource($loan);
    }

    public function return(Request $request, Loan $loan, LoanReturnService $returns): JsonResponse|LoanResource
    {
        $request->validate([
            'return_condition' => ['required', Rule::in(['GOOD', 'WORN', 'DAMAGED'])],
        ]);

        if (! in_array($loan->status, Loan::OPEN_STATUSES)) {
            return response()->json(['message' => 'Ausleihe ist bereits zurückgegeben.'], 422);
        }

        if ($loan->package_loan_id !== null) {
            return response()->json([
                'message' => 'Dieses Spiel gehört zu einer Paket-Ausleihe. Bitte das ganze Paket zurückgeben.',
            ], 422);
        }

        /** @var User $admin */
        $admin = $request->user();
        $returns->returnLoan($loan, $request->string('return_condition')->toString(), $admin);

        return new LoanResource($loan->load(['copy.game', 'user', 'extensions']));
    }

    /** Moves a running loan to another pickup location — members cannot change it themselves. */
    public function updatePickupLocation(Request $request, Loan $loan): JsonResponse|LoanResource
    {
        $request->validate([
            'pickup_location_id' => ['required', 'integer', Rule::exists('pickup_locations', 'id')->whereNull('deleted_at')->where('is_active', true)],
        ]);

        if ($loan->package_loan_id !== null) {
            return response()->json(['message' => 'Dieses Spiel gehört zu einer Paket-Ausleihe. Bitte den Ort beim Paket ändern.'], 422);
        }

        if (! in_array($loan->status, Loan::OPEN_STATUSES)) {
            return response()->json(['message' => 'Ausleihe ist bereits zurückgegeben.'], 422);
        }

        /** @var PickupLocation $location */
        $location = PickupLocation::findOrFail($request->integer('pickup_location_id'));
        /** @var User $borrower */
        $borrower = $loan->user;
        if (! $location->isAccessibleTo($borrower)) {
            return response()->json(['message' => "Dieser Ort ist für {$borrower->name} nicht freigegeben."], 422);
        }

        $loan->update(['pickup_location_id' => $location->id]);

        return new LoanResource($loan->load(['copy.game', 'user', 'extensions', 'pickupLocation']));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $loans = Loan::with(['copy.game', 'user', 'extensions'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->game_id, fn($q, $id) =>
                $q->whereHas('copy', fn($q) => $q->where('game_id', $id))
            )
            ->orderByDesc('created_at')
            ->paginate(25);

        return LoanResource::collection($loans);
    }

    public function markOverdue(Loan $loan): JsonResponse|LoanResource
    {
        if (!in_array($loan->status, ['ACTIVE', 'EXTENDED'])) {
            return response()->json(['message' => 'Ausleihe kann nicht als überfällig markiert werden.'], 422);
        }

        $loan->update(['status' => 'OVERDUE']);

        return new LoanResource($loan->load(['copy.game', 'user', 'extensions']));
    }
}

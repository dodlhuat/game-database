<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoanResource;
use App\Http\Resources\ReservationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeLoans = $user->loans()
            ->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])
            ->with(['copy.game', 'extensions'])
            ->orderBy('due_date')
            ->get();

        $loanHistory = $user->loans()
            ->where('status', 'RETURNED')
            ->with(['copy.game'])
            ->orderByDesc('returned_at')
            ->limit(10)
            ->get();

        $reservations = $user->reservations()
            ->with('game')
            ->orderBy('position')
            ->get();

        return response()->json([
            'active_loans'  => LoanResource::collection($activeLoans),
            'loan_history'  => LoanResource::collection($loanHistory),
            'reservations'  => ReservationResource::collection($reservations),
            'stats' => [
                'total_loans'        => $user->loans()->count(),
                'active_loans_count' => $activeLoans->count(),
                'overdue_count'      => $activeLoans->where('status', 'OVERDUE')->count(),
                'reservations_count' => $reservations->count(),
                'tokens_blocked'     => $user->tokens_blocked,
            ],
        ]);
    }
}

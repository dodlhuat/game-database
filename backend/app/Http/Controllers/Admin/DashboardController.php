<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'users' => [
                'total' => User::count(),
                'pending' => User::where('status', 'PENDING')->count(),
                'active' => User::where('status', 'ACTIVE')->count(),
            ],
            'loans' => [
                'active' => Loan::whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])->count(),
                'overdue' => Loan::where('status', 'OVERDUE')->count(),
                'returned_today' => Loan::where('status', 'RETURNED')
                    ->whereDate('returned_at', today())
                    ->count(),
            ],
            'copies' => [
                'to_review' => Copy::where('condition', 'REVIEW')->count(),
                // What waits where: the location of each copy's last return
                'to_review_by_location' => Copy::where('condition', 'REVIEW')
                    ->with('lastReturnedLoan')
                    ->get()
                    ->countBy(fn (Copy $c) => $c->lastReturnedLoan?->pickupLocation->name ?? 'Ohne Abholort')
                    ->sortKeys()
                    ->map(fn (int $count, string $name) => ['location' => $name, 'count' => $count])
                    ->values(),
            ],
        ]);
    }
}

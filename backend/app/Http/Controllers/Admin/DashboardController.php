<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Copy;
use App\Models\Extension;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'users' => [
                'total'   => User::count(),
                'pending' => User::where('status', 'PENDING')->count(),
                'active'  => User::where('status', 'ACTIVE')->count(),
            ],
            'loans' => [
                'active'   => Loan::whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])->count(),
                'overdue'  => Loan::where('status', 'OVERDUE')->count(),
                'returned_today' => Loan::where('status', 'RETURNED')
                    ->whereDate('returned_at', today())
                    ->count(),
            ],
            'extensions' => [
                'pending' => Extension::where('status', 'PENDING')->count(),
            ],
            'copies' => [
                'to_review' => Copy::where('condition', 'REVIEW')->count(),
            ],
        ]);
    }
}

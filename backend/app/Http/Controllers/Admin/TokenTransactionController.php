<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TokenTransactionController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $transactions = $user->tokenTransactions()
            ->with('loan.copy.game')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($transactions);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $transactions = $user
            ->tokenTransactions()
            ->with('loan.copy.game')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($transactions);
    }
}

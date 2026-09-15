<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TokenTransactionResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TokenTransactionController extends Controller
{
    public function index(User $user): AnonymousResourceCollection
    {
        $transactions = $user->tokenTransactions()
            ->with('loan.copy.game')
            ->orderByDesc('created_at')
            ->paginate(25);

        return TokenTransactionResource::collection($transactions);
    }
}

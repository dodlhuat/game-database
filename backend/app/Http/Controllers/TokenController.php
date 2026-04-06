<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    private const ALLOWED_AMOUNTS = [20, 30, 40];

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'integer', 'in:' . implode(',', self::ALLOWED_AMOUNTS)],
        ]);

        $user = $request->user();

        if (!$user->isMember() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nur Mitglieder können Token kaufen.'], 403);
        }

        $user->increment('tokens', $request->amount);
        $user->refresh();

        return response()->json([
            'message' => "{$request->amount} Token wurden deinem Konto hinzugefügt.",
            'user'    => new UserResource($user),
        ]);
    }
}

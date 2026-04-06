<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Notifications\WelcomeMemberNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function upgrade(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'USER') {
            return response()->json(['message' => 'Nur registrierte User können Mitglied werden.'], 422);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
        ]);

        $user->role                  = 'MEMBER';
        $user->tokens               += 20;
        $user->membership_expires_at = now()->addYear();
        $user->address               = $validated['address'];
        $user->save();

        $user->notify(new WelcomeMemberNotification());

        return response()->json([
            'message' => 'Willkommen als Mitglied! Du hast 20 Token erhalten.',
            'user'    => new UserResource($user),
        ]);
    }

    public function renew(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'MEMBER') {
            return response()->json(['message' => 'Nur Mitglieder können verlängern.'], 422);
        }

        if ($user->membership_expires_at === null) {
            return response()->json(['message' => 'Keine aktive Mitgliedschaft gefunden.'], 422);
        }

        $monthsRemaining = now()->diffInMonths($user->membership_expires_at, false);
        if ($monthsRemaining > 3) {
            return response()->json([
                'message' => 'Die Mitgliedschaft kann erst 3 Monate vor Ablauf verlängert werden.',
            ], 422);
        }

        // Extend from current expiry (or from now if already expired)
        $base = $user->membership_expires_at->isFuture()
            ? $user->membership_expires_at
            : now();

        $user->membership_expires_at = $base->addYear();
        $user->tokens                += 20;
        $user->renewal_reminder_sent_at = null; // allow reminder to be sent again next cycle
        $user->save();

        return response()->json([
            'message' => 'Mitgliedschaft verlängert! Du hast 20 Token erhalten.',
            'user'    => new UserResource($user),
        ]);
    }
}

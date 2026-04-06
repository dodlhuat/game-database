<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'E-Mail oder Passwort ist falsch.',
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Bitte bestätige zuerst deine E-Mail-Adresse.',
                'reason'  => 'email_not_verified',
            ], 403);
        }

        if ($user->status === 'PENDING') {
            return response()->json([
                'message' => 'Dein Konto wartet noch auf Freischaltung.',
                'status'  => 'PENDING',
            ], 403);
        }

        if ($user->status === 'REJECTED') {
            return response()->json([
                'message' => 'Deine Registrierung wurde abgelehnt.',
                'status'  => 'REJECTED',
            ], 403);
        }

        if ($user->status === 'SUSPENDED') {
            return response()->json([
                'message' => 'Dein Konto ist gesperrt. Bitte kontaktiere uns.',
                'status'  => 'SUSPENDED',
            ], 403);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Erfolgreich abgemeldet.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}

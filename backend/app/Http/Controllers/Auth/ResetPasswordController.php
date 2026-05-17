<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Passwort wurde erfolgreich zurückgesetzt.']);
        }

        return response()->json([
            'message' => match ($status) {
                Password::INVALID_TOKEN => 'Dieser Link ist ungültig oder bereits abgelaufen.',
                Password::INVALID_USER  => 'Kein Konto mit dieser E-Mail-Adresse gefunden.',
                default                 => 'Fehler beim Zurücksetzen des Passworts.',
            },
        ], 422);
    }
}

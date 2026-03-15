<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'newsletter_opt_in' => ['sometimes', 'boolean'],
            'current_password'  => ['required_with:new_password', 'string'],
            'new_password'      => ['nullable', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Das aktuelle Passwort ist falsch.'], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        if (array_key_exists('newsletter_opt_in', $validated)) {
            $user->newsletter_opt_in = $validated['newsletter_opt_in'];
        }

        $user->save();

        return response()->json(['message' => 'Konto aktualisiert.']);
    }
}

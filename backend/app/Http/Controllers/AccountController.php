<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Rules\AustrianPostalCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', new AustrianPostalCode],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'newsletter_opt_in' => ['sometimes', 'boolean'],
            'current_password' => ['required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        if (isset($validated['new_password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Das aktuelle Passwort ist falsch.'], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        if (array_key_exists('newsletter_opt_in', $validated)) {
            $user->newsletter_opt_in = $validated['newsletter_opt_in'];
        }

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('street', $validated)) {
            $user->street = $validated['street'];
        }

        if (array_key_exists('postal_code', $validated)) {
            $user->postal_code = $validated['postal_code'];
        }

        if (array_key_exists('city', $validated)) {
            $user->city = $validated['city'];
        }

        if (array_key_exists('date_of_birth', $validated)) {
            $user->date_of_birth = $validated['date_of_birth'];
        }

        $user->save();

        return response()->json([
            'message' => 'Konto aktualisiert.',
            'user' => new UserResource($user),
        ]);
    }
}

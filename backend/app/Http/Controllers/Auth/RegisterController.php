<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\TermsVersion;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $latestTerms = TermsVersion::orderByDesc('published_at')->first();

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password,
            'role'              => 'USER',
            'status'            => 'PENDING',
            'newsletter_opt_in' => $request->boolean('newsletter_opt_in', false),
            'terms_accepted_at' => now(),
            'terms_version'     => $latestTerms?->version,
        ]);

        $user->notify(new VerifyEmailNotification());

        return response()->json([
            'message' => 'Registrierung erfolgreich. Bitte bestätige deine E-Mail-Adresse.',
            'user'    => new UserResource($user),
        ], 201);
    }
}

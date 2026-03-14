<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\TermsVersion;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $latestTerms = TermsVersion::orderByDesc('published_at')->first();

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password,
            'role'              => 'MEMBER',
            'status'            => 'PENDING',
            'newsletter_opt_in' => $request->boolean('newsletter_opt_in', false),
            'terms_accepted_at' => now(),
            'terms_version'     => $latestTerms?->version,
        ]);

        // Alle Admins per Mail benachrichtigen
        $admins = User::where('role', 'ADMIN')->where('status', 'ACTIVE')->get();
        Notification::send($admins, new NewUserRegistered($user));

        return response()->json([
            'message' => 'Registrierung erfolgreich. Dein Konto wird geprüft und freigeschaltet.',
            'user'    => new UserResource($user),
        ], 201);
    }
}

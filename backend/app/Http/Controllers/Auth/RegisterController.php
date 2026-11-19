<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\TermsVersion;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    private const SUCCESS_MESSAGE = 'Registrierung erfolgreich. Bitte bestätige deine E-Mail-Adresse.';

    public function store(RegisterRequest $request): JsonResponse
    {
        if (config('app.maintenance_mode')) {
            return response()->json([
                'message' => 'Wegen Wartungsarbeiten sind Registrierungen gerade nicht möglich.',
                'reason' => 'maintenance',
            ], 503);
        }

        // Honeypot: the hidden "website" field is only ever filled by bots.
        // Answer exactly like a real sign-up so the bot can't tell it was
        // caught (and can't learn to skip the field), but create nothing and
        // send no email.
        if (filled($request->input('website'))) {
            Log::info('Registration honeypot triggered', ['ip' => $request->ip()]);

            $decoy = new User([
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'USER',
                'status' => 'PENDING',
                'newsletter_opt_in' => $request->boolean('newsletter_opt_in', false),
            ]);

            return response()->json([
                'message' => self::SUCCESS_MESSAGE,
                'token' => random_int(1000, 99999).'|'.Str::random(40),
                'user' => new UserResource($decoy),
            ], 201);
        }

        $latestTerms = TermsVersion::orderByDesc('published_at')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'USER',
            'status' => 'PENDING',
            'newsletter_opt_in' => $request->boolean('newsletter_opt_in', false),
            'terms_accepted_at' => now(),
            'terms_version' => $latestTerms?->version,
            'last_seen_at' => now(),
        ]);

        $user->notify(new VerifyEmailNotification);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => self::SUCCESS_MESSAGE,
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }
}

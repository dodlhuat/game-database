<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id): RedirectResponse
    {
        $frontendUrl = config('frontend.url', env('FRONTEND_URL', 'http://localhost:3000'));

        if (!$request->hasValidSignature()) {
            return redirect($frontendUrl . '/email-verified?error=invalid_link');
        }

        $user = User::find($id);

        if (!$user) {
            return redirect($frontendUrl . '/email-verified?error=invalid_link');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($frontendUrl . '/email-verified?already=1');
        }

        $user->markEmailAsVerified();
        $user->status = 'ACTIVE';
        $user->save();

        return redirect($frontendUrl . '/email-verified?success=1');
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'E-Mail-Adresse bereits bestätigt.'], 422);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json(['message' => 'Bestätigungs-E-Mail wurde erneut gesendet.']);
    }
}

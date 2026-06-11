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
        $frontendUrl = config('frontend.url');

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
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // Keine Information preisgeben ob die E-Mail existiert
        if (!$user || $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Bestätigungs-E-Mail wurde erneut gesendet.']);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json(['message' => 'Bestätigungs-E-Mail wurde erneut gesendet.']);
    }
}

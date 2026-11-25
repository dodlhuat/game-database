<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Leaving the newsletter without logging in. The signed link comes from the
 * newsletter mail itself — as the footer link (via the frontend page, which
 * asks before it posts here) and as the List-Unsubscribe header, which mail
 * clients post to directly for their own one-click "unsubscribe" button.
 */
class NewsletterUnsubscribeController extends Controller
{
    public function __invoke(Request $request, int $user): JsonResponse
    {
        $member = $request->hasValidSignature() ? User::find($user) : null;
        if (! $member) {
            return response()->json(['message' => 'Dieser Abmeldelink ist ungültig.', 'reason' => 'invalid_link'], 403);
        }

        $member->forceFill(['newsletter_opt_in' => false])->save();

        return response()->json(['message' => 'Du bekommst keinen Newsletter mehr.']);
    }
}

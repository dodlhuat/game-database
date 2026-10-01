<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\MembershipCancellationConfirmation;
use App\Notifications\MembershipCancellationRequested;
use App\Rules\Iban;
use App\Services\MembershipCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use RuntimeException;

class MembershipCancellationController extends Controller
{
    public function __construct(private MembershipCancellationService $service) {}

    /** What the user would get back if they cancelled now. */
    public function preview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! in_array($user->role, ['MEMBER', 'SUPPORTER'], true)) {
            return response()->json(['message' => 'Nur Mitglieder können kündigen.'], 422);
        }

        return response()->json($this->service->preview($user));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! in_array($user->role, ['MEMBER', 'SUPPORTER'], true)) {
            return response()->json(['message' => 'Nur Mitglieder können kündigen.'], 422);
        }

        $preview = $this->service->preview($user);
        if ($preview['blockers'] !== []) {
            return response()->json([
                'message' => 'Die Kündigung ist nicht möglich, solange Spiele ausgeliehen oder Token als Kaution blockiert sind.',
                'blockers' => $preview['blockers'],
            ], 422);
        }

        $needsAccount = $preview['refund_cents'] > 0;

        $validated = $request->validate([
            'confirm' => ['accepted'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'account_holder' => [Rule::requiredIf($needsAccount), 'nullable', 'string', 'max:255'],
            'iban' => [Rule::requiredIf($needsAccount), 'nullable', new Iban],
        ]);

        try {
            $cancellation = $this->service->cancel($user, $validated);
        } catch (RuntimeException) {
            return response()->json(['message' => 'Die Kündigung ist nicht mehr möglich.'], 422);
        }

        $admins = User::where('role', 'ADMIN')->get();
        Notification::send($admins, new MembershipCancellationRequested($cancellation, $user));
        $user->notify(new MembershipCancellationConfirmation($cancellation));

        return response()->json([
            'message' => 'Deine Mitgliedschaft wurde gekündigt.',
            'refund_cents' => $cancellation->refund_cents,
            'refund_tokens' => $cancellation->refund_tokens,
            'user' => new UserResource($user->fresh()),
        ]);
    }
}

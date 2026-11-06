<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreExtensionRequest;
use App\Http\Resources\ExtensionResource;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\User;
use App\Services\ExtensionPolicy;
use Illuminate\Http\JsonResponse;

class ExtensionController extends Controller
{
    public function __construct(private ExtensionPolicy $policy) {}

    public function store(StoreExtensionRequest $request, Loan $loan): JsonResponse|ExtensionResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('extend', $loan);

        if (! $user->isAdmin() && ! $user->isMember()) {
            return response()->json([
                'message' => 'Eine aktive Mitgliedschaft ist erforderlich.',
                'reason' => 'membership_required',
            ], 403);
        }

        $loan->load(['copy.game.reservations', 'extensions']);
        $setting = LoanSetting::instance();

        $blocked = $this->blockedResponse($loan, $setting);
        if ($blocked) {
            return $blocked;
        }

        // Nur prüfen: abgebucht wird erst, wenn der Admin den Antrag genehmigt.
        $cost = $this->policy->nextCost($loan, $setting);
        if (! $user->isAdmin() && ! $user->hasEnoughFreeTokens($cost)) {
            return response()->json([
                'message' => "Nicht genug Token. Diese Verlängerung kostet {$cost} Token.",
                'reason' => 'insufficient_tokens',
                'extension_cost' => $cost,
            ], 402);
        }

        $extension = $loan->extensions()->create([
            'requested_at' => now(),
            'requested_due_date' => $request->requested_due_date,
            'status' => 'PENDING',
        ]);

        return new ExtensionResource($extension);
    }

    private function blockedResponse(Loan $loan, LoanSetting $setting): ?JsonResponse
    {
        return match ($this->policy->blockReason($loan, $setting)) {
            ExtensionPolicy::BLOCK_NOT_EXTENDABLE => response()->json(['message' => 'Für diese Ausleihe kann keine Verlängerung beantragt werden.'], 422),
            ExtensionPolicy::BLOCK_PENDING => response()->json(['message' => 'Es gibt bereits einen offenen Verlängerungsantrag.'], 422),
            ExtensionPolicy::BLOCK_MAX_REACHED => response()->json(['message' => "Diese Ausleihe kann nicht weiter verlängert werden (Maximum: {$setting->max_extensions})."], 422),
            ExtensionPolicy::BLOCK_RESERVED => response()->json([
                'message' => 'Dieses Spiel ist bereits reserviert und kann deshalb nicht verlängert werden.',
                'reason' => 'game_reserved',
            ], 409),
            default => null,
        };
    }
}

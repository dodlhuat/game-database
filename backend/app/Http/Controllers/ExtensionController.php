<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreExtensionRequest;
use App\Http\Resources\ExtensionResource;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\ExtensionPolicy;
use App\Services\TokenWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExtensionController extends Controller
{
    public function __construct(
        private ExtensionPolicy $policy,
        private TokenWallet $wallet,
    ) {}

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

        return DB::transaction(function () use ($request, $user, $loan): JsonResponse|ExtensionResource {
            // Sperre, damit ein doppelter Klick nicht zweimal verlängert und abbucht
            $locked = Loan::with(['copy.game.reservations', 'extensions'])->lockForUpdate()->findOrFail($loan->id);
            $setting = LoanSetting::instance();

            $blocked = $this->blockedResponse($locked, $setting);
            if ($blocked) {
                return $blocked;
            }

            $cost = $user->isAdmin() ? 0 : $this->policy->nextCost($locked, $setting);
            if (! $user->hasEnoughFreeTokens($cost)) {
                return response()->json([
                    'message' => "Nicht genug Token. Diese Verlängerung kostet {$cost} Token.",
                    'reason' => 'insufficient_tokens',
                    'extension_cost' => $cost,
                ], 402);
            }

            if ($cost > 0) {
                $this->wallet->spend($user, $cost);
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'loan_id' => $locked->id,
                    'type' => 'EXTENSION',
                    'amount' => -$cost,
                    'description' => 'Verlängerung: '.$locked->copy?->game?->title,
                ]);
            }

            $extension = $locked->extensions()->create([
                'requested_at' => now(),
                'requested_due_date' => $request->requested_due_date,
                'status' => 'APPROVED',
                'tokens_charged' => $cost,
            ]);

            $locked->update([
                'due_date' => $request->requested_due_date,
                'status' => 'EXTENDED',
            ]);

            return new ExtensionResource($extension);
        });
    }

    private function blockedResponse(Loan $loan, LoanSetting $setting): ?JsonResponse
    {
        return match ($this->policy->blockReason($loan, $setting)) {
            ExtensionPolicy::BLOCK_NOT_EXTENDABLE => response()->json(['message' => 'Diese Ausleihe kann nicht verlängert werden.'], 422),
            ExtensionPolicy::BLOCK_MAX_REACHED => response()->json([
                'message' => 'Dieses Spiel wurde bereits zweimal verlängert und kann nicht weiter verlängert werden.',
                'reason' => 'max_extensions_reached',
            ], 422),
            ExtensionPolicy::BLOCK_RESERVED => response()->json([
                'message' => 'Dieses Spiel ist bereits reserviert und kann deshalb nicht verlängert werden.',
                'reason' => 'game_reserved',
            ], 409),
            default => null,
        };
    }
}

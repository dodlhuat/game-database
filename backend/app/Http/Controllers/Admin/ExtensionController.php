<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExtensionResource;
use App\Models\Extension;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\ExtensionPolicy;
use App\Services\TokenWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ExtensionController extends Controller
{
    public function __construct(
        private ExtensionPolicy $policy,
        private TokenWallet $wallet,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $extensions = Extension::with(['loan.copy.game.reservations', 'loan.user', 'loan.extensions'])
            ->when($request->status ?? 'PENDING', fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('requested_at')
            ->paginate(25);

        return ExtensionResource::collection($extensions);
    }

    public function approve(Request $request, Extension $extension): JsonResponse|ExtensionResource
    {
        $error = DB::transaction(function () use ($request, $extension): ?JsonResponse {
            // Sperre, damit ein doppelter Klick nicht zweimal abbucht
            $locked = Extension::lockForUpdate()->findOrFail($extension->id);

            if ($locked->status !== 'PENDING') {
                return response()->json(['message' => 'Antrag wurde bereits bearbeitet.'], 422);
            }

            /** @var Loan $loan */
            $loan = $locked->loan()->with(['copy.game.reservations', 'extensions', 'user'])->firstOrFail();

            if ($this->policy->isReservedByOthers($loan)) {
                return response()->json([
                    'message' => 'Das Spiel wurde inzwischen reserviert. Bitte lehne den Antrag ab.',
                    'reason' => 'game_reserved',
                ], 409);
            }

            /** @var User $user */
            $user = $loan->user;
            $cost = $user->isAdmin() ? 0 : $this->policy->nextCost($loan, LoanSetting::instance());

            if (! $user->hasEnoughFreeTokens($cost)) {
                return response()->json([
                    'message' => "Der Benutzer hat nicht genug Token. Diese Verlängerung kostet {$cost} Token.",
                    'reason' => 'insufficient_tokens',
                ], 422);
            }

            if ($cost > 0) {
                $this->wallet->spend($user, $cost);
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'loan_id' => $loan->id,
                    'type' => 'EXTENSION',
                    'amount' => -$cost,
                    'description' => 'Verlängerung: '.$loan->copy?->game?->title,
                ]);
            }

            $locked->update([
                'status' => 'APPROVED',
                'admin_note' => $request->admin_note,
                'tokens_charged' => $cost,
            ]);

            $loan->update([
                'due_date' => $locked->requested_due_date,
                'status' => 'EXTENDED',
            ]);

            return null;
        });

        if ($error) {
            return $error;
        }

        return new ExtensionResource($extension->fresh(['loan.copy.game', 'loan.user']));
    }

    public function reject(Request $request, Extension $extension): JsonResponse|ExtensionResource
    {
        if ($extension->status !== 'PENDING') {
            return response()->json(['message' => 'Antrag wurde bereits bearbeitet.'], 422);
        }

        $extension->update([
            'status' => 'REJECTED',
            'admin_note' => $request->admin_note,
        ]);

        return new ExtensionResource($extension->load(['loan.copy.game', 'loan.user']));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\MembershipPayment;
use App\Models\TokenPurchase;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Behind the return page: after Mollie sends the member back, this asks
 * Mollie for the outcome and books it. Works without the webhook too, so
 * payments confirm even where Mollie cannot reach us (local development).
 */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function show(Request $request, string $kind, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = match ($kind) {
            PaymentService::KIND_TOKENS => TokenPurchase::whereKey($id)->where('user_id', $user->id)->first(),
            PaymentService::KIND_MEMBERSHIP => MembershipPayment::whereKey($id)->where('user_id', $user->id)->first(),
            default => null,
        };

        if (! $record) {
            return response()->json(['message' => 'Zahlung nicht gefunden.'], 404);
        }

        try {
            $outcome = $this->payments->sync($record);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Zahlung konnte nicht überprüft werden.'], 502);
        }

        return response()->json([
            'status' => $outcome,
            'message' => $this->message($record, $outcome),
            'user' => new UserResource($user->fresh()),
        ]);
    }

    private function message(TokenPurchase|MembershipPayment $record, string $outcome): string
    {
        if ($record instanceof MembershipPayment && $record->status === MembershipPayment::STATUS_DUPLICATE) {
            return 'Deine Mitgliedschaft war zu diesem Zeitpunkt schon bezahlt – du hast doppelt gezahlt. Wir erstatten dir diesen Betrag in den nächsten Tagen, an deiner Mitgliedschaft ändert sich nichts.';
        }

        return match ($outcome) {
            PaymentService::PAID => $record instanceof TokenPurchase
                ? "{$record->token_amount} Token wurden deinem Konto gutgeschrieben."
                : ($record->type === MembershipPayment::TYPE_RENEWAL
                    ? 'Deine Mitgliedschaft wurde verlängert.'
                    : 'Willkommen! Deine Mitgliedschaft ist aktiv.'),
            PaymentService::FAILED => 'Die Zahlung wurde abgebrochen oder ist fehlgeschlagen. Es wurde nichts abgebucht.',
            PaymentService::REFUNDED => 'Diese Zahlung wurde erstattet.',
            default => 'Deine Zahlung wird noch geprüft.',
        };
    }
}

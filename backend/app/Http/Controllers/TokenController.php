<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\TokenPurchase;
use App\Models\User;
use App\Services\StripeClient;
use App\Services\TokenPurchaseCompleter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class TokenController extends Controller
{
    public function __construct(
        private StripeClient $stripe,
        private TokenPurchaseCompleter $completer,
    ) {}

    /**
     * Public price list for the token packages — token amount + price, read
     * straight from config('tokens.packages') so the frontend never has to
     * hardcode prices.
     */
    public function packages(): JsonResponse
    {
        $currency = config('tokens.currency');

        $packages = [];
        foreach (config('tokens.packages') as $amount => $priceCents) {
            $packages[] = [
                'amount' => $amount,
                'price_cents' => $priceCents,
                'currency' => $currency,
            ];
        }

        return response()->json(['data' => $packages]);
    }

    /**
     * Creates a Stripe PaymentIntent for a token package. The price is
     * looked up server-side from config('tokens.packages') — the client only
     * ever picks a token amount, never a price.
     */
    public function checkout(Request $request): JsonResponse
    {
        $packages = config('tokens.packages');

        $request->validate([
            'amount' => ['required', 'integer', Rule::in(array_keys($packages))],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user->isMember() && ! $user->isAdmin()) {
            return response()->json(['message' => 'Nur Mitglieder können Token kaufen.'], 403);
        }

        $amount = $request->integer('amount');
        $priceCents = $packages[$amount];
        $currency = config('tokens.currency');

        try {
            $intent = $this->stripe->createPaymentIntent($priceCents, $currency, [
                'user_id' => (string) $user->id,
                'token_amount' => (string) $amount,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Zahlung konnte nicht vorbereitet werden.'], 502);
        }

        TokenPurchase::create([
            'user_id' => $user->id,
            'provider_payment_intent_id' => $intent['id'],
            'token_amount' => $amount,
            'price_cents' => $priceCents,
            'currency' => $currency,
            'status' => 'CREATED',
            'payload' => $intent,
        ]);

        return response()->json(['clientSecret' => $intent['client_secret']]);
    }

    /**
     * Confirms a previously created Stripe PaymentIntent and credits the
     * tokens. Idempotent: calling this again for an already-completed
     * purchase just returns the current state instead of crediting twice.
     *
     * The client only tells us which payment intent to check — the actual
     * status is always re-fetched from Stripe, never trusted from the client.
     */
    public function confirm(Request $request, string $paymentIntentId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $purchase = TokenPurchase::where('provider_payment_intent_id', $paymentIntentId)
            ->where('user_id', $user->id)
            ->first();

        if (! $purchase) {
            return response()->json(['message' => 'Bestellung nicht gefunden.'], 404);
        }

        if ($purchase->status === 'COMPLETED') {
            return response()->json([
                'message' => 'Token wurden bereits gutgeschrieben.',
                'user' => new UserResource($user->fresh()),
            ]);
        }

        try {
            $intent = $this->stripe->retrievePaymentIntent($paymentIntentId);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Zahlung konnte nicht überprüft werden.'], 502);
        }

        $chargeId = $intent['latest_charge'] ?? null;

        if (($intent['status'] ?? null) !== 'succeeded' || ! is_string($chargeId)) {
            $purchase->update(['status' => 'FAILED', 'payload' => $intent]);

            return response()->json(['message' => 'Zahlung wurde nicht abgeschlossen.'], 422);
        }

        $this->completer->complete($purchase, $chargeId, $intent);

        return response()->json([
            'message' => "{$purchase->token_amount} Token wurden deinem Konto hinzugefügt.",
            'user' => new UserResource($user->fresh()),
        ]);
    }
}

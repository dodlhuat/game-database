<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\TokenPurchase;
use App\Models\User;
use App\Services\PayPalClient;
use App\Services\TokenPurchaseCompleter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class TokenController extends Controller
{
    public function __construct(
        private PayPalClient $payPal,
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
     * Creates a PayPal order for a token package. The price is looked up
     * server-side from config('tokens.packages') — the client only ever
     * picks a token amount, never a price.
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
            $order = $this->payPal->createOrder($priceCents, $currency, "user-{$user->id}-tokens-{$amount}-".uniqid());
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'PayPal-Bestellung konnte nicht erstellt werden.'], 502);
        }

        $purchase = TokenPurchase::create([
            'user_id' => $user->id,
            'paypal_order_id' => $order['id'],
            'token_amount' => $amount,
            'price_cents' => $priceCents,
            'currency' => $currency,
            'status' => 'CREATED',
            'payload' => $order,
        ]);

        return response()->json(['orderID' => $purchase->paypal_order_id]);
    }

    /**
     * Captures a previously created PayPal order and credits the tokens.
     * Idempotent: calling this again for an already-completed purchase just
     * returns the current state instead of crediting twice.
     */
    public function capture(Request $request, string $orderId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $purchase = TokenPurchase::where('paypal_order_id', $orderId)
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
            $result = $this->payPal->captureOrder($orderId);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Zahlung konnte nicht abgeschlossen werden.'], 502);
        }

        $capture = $result['purchase_units'][0]['payments']['captures'][0] ?? null;
        $captureStatus = $capture['status'] ?? $result['status'] ?? null;

        if ($captureStatus !== 'COMPLETED' || ! isset($capture['id'])) {
            $purchase->update(['status' => 'FAILED', 'payload' => $result]);

            return response()->json(['message' => 'Zahlung wurde nicht abgeschlossen.'], 422);
        }

        $this->completer->complete($purchase, $capture['id'], $result);

        return response()->json([
            'message' => "{$purchase->token_amount} Token wurden deinem Konto hinzugefügt.",
            'user' => new UserResource($user->fresh()),
        ]);
    }
}

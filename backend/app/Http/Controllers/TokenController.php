<?php

namespace App\Http\Controllers;

use App\Models\TokenPurchase;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class TokenController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /**
     * Public price list for the token packages — token amount + price, read
     * straight from config('tokens.packages') so the frontend never has to
     * hardcode prices.
     */
    public function packages(): JsonResponse
    {
        $currency = config()->string('tokens.currency');

        $packages = [];
        foreach (config()->array('tokens.packages') as $amount => $priceCents) {
            $packages[] = [
                'amount' => $amount,
                'price_cents' => $priceCents,
                'currency' => $currency,
            ];
        }

        return response()->json(['data' => $packages]);
    }

    /**
     * Opens a Mollie checkout for a token package. The price is looked up
     * server-side from config('tokens.packages') — the client only ever
     * picks a token amount, never a price. Tokens are credited once Mollie
     * reports the payment as paid (return page or webhook).
     */
    public function checkout(Request $request): JsonResponse
    {
        $packages = config()->array('tokens.packages');

        $request->validate([
            'amount' => ['required', 'integer', Rule::in(array_keys($packages))],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user->isMember() && ! $user->isAdmin()) {
            return response()->json(['message' => 'Nur Mitglieder können Token kaufen.'], 403);
        }

        $amount = $request->integer('amount');

        $purchase = TokenPurchase::create([
            'user_id' => $user->id,
            'provider' => 'mollie',
            'token_amount' => $amount,
            'price_cents' => config()->integer("tokens.packages.{$amount}"),
            'currency' => config()->string('tokens.currency'),
            'status' => 'CREATED',
        ]);

        try {
            $checkoutUrl = $this->payments->startCheckout($purchase, "AUA Spieleausleihe · {$amount} Token");
        } catch (Throwable $e) {
            report($e);
            $purchase->update(['status' => 'FAILED']);

            return response()->json(['message' => 'Zahlung konnte nicht vorbereitet werden.'], 502);
        }

        return response()->json(['checkoutUrl' => $checkoutUrl, 'id' => $purchase->id]);
    }
}

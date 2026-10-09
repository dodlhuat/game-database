<?php

namespace App\Http\Controllers;

use App\Models\MembershipPayment;
use App\Models\TokenPurchase;
use App\Services\MembershipPaymentCompleter;
use App\Services\StripeClient;
use App\Services\TokenPurchaseCompleter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for the token-purchase flow: if the client's confirm call
 * (TokenController::confirm) never reaches the server — tab closed, network
 * drop after Stripe already confirmed the payment — this webhook is what
 * actually credits the tokens. TokenPurchaseCompleter is idempotent, so it's
 * harmless if both the client confirm and this webhook fire for the same
 * payment intent.
 *
 * No Sanctum auth (Stripe calls this directly) — authenticity is established
 * via Stripe's webhook signature instead, verified before any processing.
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeClient $stripe,
        private TokenPurchaseCompleter $completer,
        private MembershipPaymentCompleter $membershipCompleter,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        if (! $this->stripe->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Stripe webhook signature verification failed.');

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $decoded = json_decode($payload, true);
        /** @var array<string, mixed> $event */
        $event = is_array($decoded) ? $decoded : [];

        match ($event['type'] ?? null) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
            'charge.refunded' => $this->handleChargeRefunded($event),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }

    /** @param  array<string, mixed>  $event */
    private function handlePaymentIntentSucceeded(array $event): void
    {
        $intent = $this->eventObject($event);
        $paymentIntentId = $intent['id'] ?? null;
        $chargeId = $intent['latest_charge'] ?? null;

        if (! is_string($paymentIntentId) || ! is_string($chargeId)) {
            return;
        }

        $membership = MembershipPayment::where('provider_payment_intent_id', $paymentIntentId)->first();
        if ($membership) {
            $this->membershipCompleter->complete($membership, $chargeId, $intent);

            return;
        }

        $purchase = TokenPurchase::where('provider_payment_intent_id', $paymentIntentId)->first();

        if (! $purchase) {
            Log::warning('Stripe webhook: no matching token_purchase for payment intent.', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        $this->completer->complete($purchase, $chargeId, $intent);
    }

    /**
     * The Stripe object a webhook event is about (`data.object`), or an empty
     * array for malformed payloads.
     *
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function eventObject(array $event): array
    {
        $data = $event['data'] ?? null;
        $object = is_array($data) ? ($data['object'] ?? null) : null;

        /** @var array<string, mixed> $result */
        $result = is_array($object) ? $object : [];

        return $result;
    }

    /**
     * Only flags the purchase as REFUNDED for bookkeeping/admin visibility —
     * deliberately does not auto-deduct tokens already spent on loans.
     *
     * @param  array<string, mixed>  $event
     */
    private function handleChargeRefunded(array $event): void
    {
        $charge = $this->eventObject($event);
        $chargeId = $charge['id'] ?? null;

        if (! is_string($chargeId)) {
            return;
        }

        TokenPurchase::where('provider_charge_id', $chargeId)->update(['status' => 'REFUNDED']);
        MembershipPayment::where('provider_charge_id', $chargeId)->update(['status' => 'REFUNDED']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TokenPurchase;
use App\Services\PayPalClient;
use App\Services\TokenPurchaseCompleter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Safety net for the token-purchase flow: if the client's capture call
 * (TokenController::capture) never reaches the server — tab closed, network
 * drop after PayPal already captured the money — this webhook is what
 * actually credits the tokens. TokenPurchaseCompleter is idempotent, so it's
 * harmless if both the client capture and this webhook fire for the same
 * order.
 *
 * No Sanctum auth (PayPal calls this directly) — authenticity is established
 * via PayPal's webhook signature instead, verified before any processing.
 */
class PayPalWebhookController extends Controller
{
    public function __construct(
        private PayPalClient $payPal,
        private TokenPurchaseCompleter $completer,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $event = $request->json()->all();

        try {
            $verification = $this->payPal->verifyWebhookSignature([
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => config('services.paypal.webhook_id'),
                'webhook_event' => $event,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Signature verification failed.'], 400);
        }

        if (($verification['verification_status'] ?? null) !== 'SUCCESS') {
            Log::warning('PayPal webhook signature verification failed.', [
                'event_type' => $event['event_type'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        match ($event['event_type'] ?? null) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($event),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handleCaptureRefunded($event),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }

    /** @param  array<string, mixed>  $event */
    private function handleCaptureCompleted(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if (! is_string($captureId) || ! is_string($orderId) || ($resource['status'] ?? null) !== 'COMPLETED') {
            return;
        }

        $purchase = TokenPurchase::where('paypal_order_id', $orderId)->first();

        if (! $purchase) {
            Log::warning('PayPal webhook: no matching token_purchase for order.', ['order_id' => $orderId]);

            return;
        }

        $this->completer->complete($purchase, $captureId, $resource);
    }

    /**
     * Only flags the purchase as REFUNDED for bookkeeping/admin visibility —
     * deliberately does not auto-deduct tokens already spent on loans.
     *
     * @param  array<string, mixed>  $event
     */
    private function handleCaptureRefunded(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $links = is_array($resource['links'] ?? null) ? $resource['links'] : [];

        $captureId = null;
        foreach ($links as $link) {
            if (is_array($link) && ($link['rel'] ?? null) === 'up') {
                $captureId = basename((string) ($link['href'] ?? ''));
                break;
            }
        }

        if (! $captureId) {
            Log::warning('PayPal webhook: refund event without resolvable capture id.', ['resource' => $resource]);

            return;
        }

        TokenPurchase::where('paypal_capture_id', $captureId)->update(['status' => 'REFUNDED']);
    }
}

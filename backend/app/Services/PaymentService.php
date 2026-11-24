<?php

namespace App\Services;

use App\Models\MembershipPayment;
use App\Models\TokenPurchase;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The one place that talks to the payment provider for both kinds of
 * payment (token packages, membership fees): opens a Mollie checkout and
 * later brings our record in line with what Mollie says. Booking the
 * result is left to the existing completers, which are idempotent — the
 * return page and the webhook may both report the same payment.
 */
class PaymentService
{
    public const KIND_TOKENS = 'tokens';

    public const KIND_MEMBERSHIP = 'membership';

    /** Outcome as the return page shows it. */
    public const PAID = 'paid';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public function __construct(
        private MollieClient $mollie,
        private TokenPurchaseCompleter $tokens,
        private MembershipPaymentCompleter $memberships,
    ) {}

    /** Creates the Mollie payment for a fresh record and returns where to pay. */
    public function startCheckout(TokenPurchase|MembershipPayment $record, string $description): string
    {
        $kind = $this->kind($record);
        $redirect = config()->string('frontend.url')."/zahlung?art={$kind}&id={$record->id}";

        $payment = $this->mollie->createPayment(
            $record->price_cents,
            $record->currency,
            $description,
            $redirect,
            ['kind' => $kind, 'record_id' => (string) $record->id, 'user_id' => (string) $record->user_id],
        );

        $checkoutUrl = MollieClient::checkoutUrl($payment);
        $paymentId = $payment['id'] ?? null;
        if (! is_string($paymentId) || $checkoutUrl === null) {
            throw new RuntimeException('Mollie returned a payment without id or checkout link.');
        }

        $record->update([
            'provider' => 'mollie',
            'provider_payment_id' => $paymentId,
            'payload' => $payment,
        ]);

        return $checkoutUrl;
    }

    /**
     * Asks Mollie about the payment and books the result: paid → credit or
     * activate, cancelled/expired/failed → FAILED, refunded or charged back
     * → REFUNDED (tokens already spent are deliberately not taken back).
     */
    public function sync(TokenPurchase|MembershipPayment $record): string
    {
        if ($record->provider !== 'mollie' || ! $record->provider_payment_id) {
            return $this->outcome($record);
        }

        $payment = $this->mollie->getPayment($record->provider_payment_id);
        $status = $payment['status'] ?? null;

        if ($status === 'paid' && $record->status !== 'COMPLETED' && $record->status !== 'REFUNDED') {
            $record instanceof TokenPurchase
                ? $this->tokens->complete($record, $record->provider_payment_id, $payment)
                : $this->memberships->complete($record, $record->provider_payment_id, $payment);
            $record->refresh();
        } elseif (in_array($status, ['canceled', 'expired', 'failed'], true) && $record->status === 'CREATED') {
            $record->update(['status' => 'FAILED', 'payload' => $payment]);
        }

        if ($record->status === 'COMPLETED' && ($this->cents($payment, 'amountRefunded') > 0 || $this->cents($payment, 'amountChargedBack') > 0)) {
            $record->update(['status' => 'REFUNDED']);
            Log::info('Mollie payment refunded or charged back.', ['payment' => $record->provider_payment_id]);
        }

        return $this->outcome($record);
    }

    public function kind(TokenPurchase|MembershipPayment $record): string
    {
        return $record instanceof TokenPurchase ? self::KIND_TOKENS : self::KIND_MEMBERSHIP;
    }

    private function outcome(TokenPurchase|MembershipPayment $record): string
    {
        return match ($record->status) {
            'COMPLETED' => self::PAID,
            'FAILED' => self::FAILED,
            'REFUNDED' => self::REFUNDED,
            default => self::PENDING,
        };
    }

    /** @param  array<string, mixed>  $payment */
    private function cents(array $payment, string $field): int
    {
        $amount = $payment[$field] ?? null;
        $value = is_array($amount) ? ($amount['value'] ?? null) : null;

        return is_numeric($value) ? (int) round(((float) $value) * 100) : 0;
    }
}

<?php

namespace App\Services;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Credits tokens for a completed Stripe charge. Idempotent and safe to call
 * from both the confirm endpoint and the webhook handler for the same
 * purchase — e.g. if the client's confirm call never reaches the server
 * (tab closed, network drop), the webhook is the fallback; if both fire,
 * only the first one to acquire the row lock actually credits tokens.
 */
class TokenPurchaseCompleter
{
    public function __construct(private TokenWallet $wallet) {}

    /** @param  array<string, mixed>  $rawPayload */
    public function complete(TokenPurchase $purchase, string $chargeId, array $rawPayload): TokenPurchase
    {
        return DB::transaction(function () use ($purchase, $chargeId, $rawPayload) {
            $locked = TokenPurchase::whereKey($purchase->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'COMPLETED') {
                return $locked ?? $purchase;
            }

            $locked->update([
                'provider_charge_id' => $chargeId,
                'status' => 'COMPLETED',
                'payload' => $rawPayload,
                'captured_at' => now(),
            ]);

            // Floor, damit eine spätere Erstattung nie mehr als den Kaufpreis ergibt
            $this->wallet->grantTokens(
                User::findOrFail($locked->user_id),
                $locked->token_amount,
                'PURCHASE',
                intdiv($locked->price_cents, $locked->token_amount),
                now()->addDays(config()->integer('membership.purchase_refund_wait_days')),
            );

            TokenTransaction::create([
                'user_id' => $locked->user_id,
                'token_purchase_id' => $locked->id,
                'type' => 'PURCHASE',
                'amount' => $locked->token_amount,
                'description' => "Stripe-Kauf (Payment Intent {$locked->provider_payment_intent_id})",
            ]);

            return $locked;
        });
    }
}

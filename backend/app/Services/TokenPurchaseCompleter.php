<?php

namespace App\Services;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Credits tokens for a completed PayPal capture. Idempotent and safe to call
 * from both the capture endpoint and the webhook handler for the same
 * purchase — e.g. if the client's capture call never reaches the server
 * (tab closed, network drop), the webhook is the fallback; if both fire,
 * only the first one to acquire the row lock actually credits tokens.
 */
class TokenPurchaseCompleter
{
    /** @param  array<string, mixed>  $rawPayload */
    public function complete(TokenPurchase $purchase, string $captureId, array $rawPayload): TokenPurchase
    {
        return DB::transaction(function () use ($purchase, $captureId, $rawPayload) {
            $locked = TokenPurchase::whereKey($purchase->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'COMPLETED') {
                return $locked ?? $purchase;
            }

            $locked->update([
                'paypal_capture_id' => $captureId,
                'status' => 'COMPLETED',
                'payload' => $rawPayload,
                'captured_at' => now(),
            ]);

            $locked->user()->increment('tokens', $locked->token_amount);

            TokenTransaction::create([
                'user_id' => $locked->user_id,
                'token_purchase_id' => $locked->id,
                'type' => 'PURCHASE',
                'amount' => $locked->token_amount,
                'description' => "PayPal-Kauf (Order {$locked->paypal_order_id})",
            ]);

            return $locked;
        });
    }
}

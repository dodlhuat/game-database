<?php

namespace App\Http\Controllers;

use App\Models\MembershipPayment;
use App\Models\TokenPurchase;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Mollie calls this whenever a payment changes (paid, cancelled, expired,
 * refunded, charged back). The request carries only the payment id and no
 * signature — the body is never trusted beyond that id: PaymentService
 * fetches the real status from Mollie with our key. Safety net for members
 * who close the tab before the return page loads.
 *
 * No Sanctum auth, since Mollie calls it directly.
 */
class MollieWebhookController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function handle(Request $request): Response
    {
        $paymentId = $request->string('id')->toString();

        $record = $paymentId === '' ? null
            : (TokenPurchase::where('provider', 'mollie')->where('provider_payment_id', $paymentId)->first()
                ?? MembershipPayment::where('provider', 'mollie')->where('provider_payment_id', $paymentId)->first());

        // Unknown ids get the same answer as known ones, so nobody can probe for them
        if (! $record) {
            Log::info('Mollie webhook for unknown payment id.', ['id' => $paymentId]);

            return response()->noContent(200);
        }

        // An exception means a 500, and Mollie retries later — what we want
        $this->payments->sync($record);

        return response()->noContent(200);
    }
}

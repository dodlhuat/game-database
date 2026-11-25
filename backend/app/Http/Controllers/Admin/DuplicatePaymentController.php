<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Membership payments that came in twice and still have to be refunded by
 * hand in the Mollie dashboard. Refunds seen at Mollie tick themselves off
 * (PaymentService::sync); this is for marking them done here as well.
 */
class DuplicatePaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $open = MembershipPayment::where('status', MembershipPayment::STATUS_DUPLICATE)
            ->orderBy('captured_at')
            ->get();
        $users = User::whereIn('id', $open->pluck('user_id'))->get()->keyBy('id');

        return response()->json([
            'data' => $open->map(fn (MembershipPayment $p) => [
                'id' => $p->id,
                'user' => ($u = $users->get($p->user_id)) ? ['id' => $u->id, 'name' => $u->name, 'email' => $u->email] : null,
                'type' => $p->type,
                'price_cents' => $p->price_cents,
                'currency' => $p->currency,
                'provider_payment_id' => $p->provider_payment_id,
                'paid_at' => $p->captured_at,
            ])->values(),
        ]);
    }

    public function markRefunded(MembershipPayment $payment): JsonResponse
    {
        if ($payment->status !== MembershipPayment::STATUS_DUPLICATE) {
            return response()->json(['message' => 'Diese Zahlung wartet nicht auf eine Erstattung.'], 422);
        }

        $payment->update(['status' => MembershipPayment::STATUS_DUP_REFUNDED]);

        return response()->json(['message' => 'Als erstattet markiert.']);
    }
}

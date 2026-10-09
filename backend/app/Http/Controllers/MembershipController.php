<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\WelcomeMemberNotification;
use App\Rules\AustrianPostalCode;
use App\Services\MembershipGrant;
use App\Services\MembershipPaymentCompleter;
use App\Services\StripeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MembershipController extends Controller
{
    public function __construct(
        private StripeClient $stripe,
        private MembershipPaymentCompleter $completer,
        private MembershipGrant $grant,
    ) {}

    /** Public price and benefits of the membership, straight from config. */
    public function pricing(): JsonResponse
    {
        return response()->json([
            'fee_cents' => config()->integer('membership.fee_cents'),
            'currency' => config()->string('tokens.currency'),
            'tokens' => config()->integer('membership.tokens'),
            'bonus_tokens' => config()->integer('membership.bonus_tokens'),
            'bonus_valid_months' => config()->integer('membership.bonus_valid_months'),
        ]);
    }

    /**
     * Creates a Stripe PaymentIntent for a membership (new member, new
     * supporter or renewal). The price always comes from config — the client
     * only picks the type. The membership itself is only activated once the
     * payment is confirmed (see confirm() and the Stripe webhook).
     */
    public function checkout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'type' => ['required', Rule::in([
                MembershipPayment::TYPE_MEMBER,
                MembershipPayment::TYPE_SUPPORTER,
                MembershipPayment::TYPE_RENEWAL,
            ])],
        ]);
        $type = $request->string('type')->toString();

        $address = [];
        if ($type === MembershipPayment::TYPE_RENEWAL) {
            if (! in_array($user->role, ['MEMBER', 'SUPPORTER'], true)) {
                return response()->json(['message' => 'Nur Mitglieder können verlängern.'], 422);
            }
            if ($user->membership_expires_at === null) {
                return response()->json(['message' => 'Keine aktive Mitgliedschaft gefunden.'], 422);
            }
            if (now()->diffInMonths($user->membership_expires_at, false) > 3) {
                return response()->json([
                    'message' => 'Die Mitgliedschaft kann erst 3 Monate vor Ablauf verlängert werden.',
                ], 422);
            }
        } else {
            if ($user->role !== 'USER') {
                return response()->json(['message' => 'Nur registrierte User können Mitglied werden.'], 422);
            }
            $address = $request->validate([
                'street' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', new AustrianPostalCode],
                'city' => ['required', 'string', 'max:255'],
            ]);
        }

        $priceCents = config()->integer('membership.fee_cents');
        $currency = config()->string('tokens.currency');

        try {
            $intent = $this->stripe->createPaymentIntent($priceCents, $currency, [
                'user_id' => (string) $user->id,
                'kind' => 'membership',
                'membership_type' => $type,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Zahlung konnte nicht vorbereitet werden.'], 502);
        }

        MembershipPayment::create([
            'user_id' => $user->id,
            'type' => $type,
            'provider_payment_intent_id' => $intent['id'],
            'price_cents' => $priceCents,
            'currency' => $currency,
            'status' => 'CREATED',
            'street' => $address['street'] ?? null,
            'postal_code' => $address['postal_code'] ?? null,
            'city' => $address['city'] ?? null,
            'payload' => $intent,
        ]);

        return response()->json([
            'clientSecret' => $intent['client_secret'],
            'price_cents' => $priceCents,
            'currency' => $currency,
        ]);
    }

    /**
     * Confirms a previously created PaymentIntent and activates the
     * membership. Idempotent. The status is always re-fetched from Stripe.
     */
    public function confirm(Request $request, string $paymentIntentId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payment = MembershipPayment::where('provider_payment_intent_id', $paymentIntentId)
            ->where('user_id', $user->id)
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Zahlung nicht gefunden.'], 404);
        }

        if ($payment->status === 'COMPLETED') {
            return response()->json([
                'message' => 'Die Mitgliedschaft ist bereits aktiv.',
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
            $payment->update(['status' => 'FAILED', 'payload' => $intent]);

            return response()->json(['message' => 'Zahlung wurde nicht abgeschlossen.'], 422);
        }

        $this->completer->complete($payment, $chargeId, $intent);

        return response()->json([
            'message' => $payment->type === MembershipPayment::TYPE_RENEWAL
                ? 'Mitgliedschaft verlängert!'
                : 'Willkommen! Deine Mitgliedschaft ist aktiv.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /** Supporter who already paid the fee upgrades to a full membership. */
    public function activateFullMembership(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'SUPPORTER') {
            return response()->json(['message' => 'Nur außerordentliche Mitglieder können auf ein Vollmitglied upgraden.'], 422);
        }

        $user->role = 'MEMBER';
        $user->save();
        $this->grant->grantMemberPackage($user);

        $user->notify(new WelcomeMemberNotification);

        return response()->json([
            'message' => 'Willkommen als Vollmitglied! Du hast '.config()->integer('membership.tokens').' Token und '.config()->integer('membership.bonus_tokens').' Bonus-Token erhalten.',
            'user' => new UserResource($user),
        ]);
    }
}

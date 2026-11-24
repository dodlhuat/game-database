<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\WelcomeMemberNotification;
use App\Rules\AustrianPostalCode;
use App\Services\MembershipGrant;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MembershipController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private MembershipGrant $grant,
    ) {}

    /** Public price and benefits of the membership, straight from config. */
    public function pricing(): JsonResponse
    {
        return response()->json([
            'fee_cents' => config()->integer('membership.fee_cents'),
            'currency' => config()->string('tokens.currency'),
            'bonus_tokens' => config()->integer('membership.bonus_tokens'),
            'bonus_value_cents' => config()->integer('membership.bonus_tokens') * config()->integer('tokens.token_value_cents'),
            'bonus_valid_months' => config()->integer('membership.bonus_valid_months'),
        ]);
    }

    /**
     * Opens a Mollie checkout for a membership (new member, new supporter or
     * renewal). The price always comes from config — the client only picks
     * the type. The membership itself is only activated once Mollie reports
     * the payment as paid (return page or webhook, see PaymentService).
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

        $payment = MembershipPayment::create([
            'user_id' => $user->id,
            'provider' => 'mollie',
            'type' => $type,
            'price_cents' => config()->integer('membership.fee_cents'),
            'currency' => config()->string('tokens.currency'),
            'status' => 'CREATED',
            'street' => $address['street'] ?? null,
            'postal_code' => $address['postal_code'] ?? null,
            'city' => $address['city'] ?? null,
        ]);

        $description = $type === MembershipPayment::TYPE_RENEWAL
            ? 'AUA Spieleausleihe · Verlängerung Mitgliedschaft'
            : 'AUA Spieleausleihe · Mitgliedsbeitrag';

        try {
            $checkoutUrl = $this->payments->startCheckout($payment, $description);
        } catch (Throwable $e) {
            report($e);
            $payment->update(['status' => 'FAILED']);

            return response()->json(['message' => 'Zahlung konnte nicht vorbereitet werden.'], 502);
        }

        return response()->json(['checkoutUrl' => $checkoutUrl, 'id' => $payment->id]);
    }

    /** Supporter who already paid the fee upgrades to a full membership. */
    public function activateFullMembership(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'SUPPORTER') {
            return response()->json(['message' => 'Nur außerordentliche Mitglieder können auf ein Vollmitglied upgraden.'], 422);
        }
        // An expired supporter has to renew first, otherwise the upgrade would
        // hand out the bonus package without a paid membership behind it.
        if (! $user->isSupporter()) {
            return response()->json(['message' => 'Deine Mitgliedschaft ist abgelaufen. Bitte verlängere sie zuerst.'], 422);
        }

        $user->role = 'MEMBER';
        $user->save();
        $this->grant->grantMemberPackage($user);

        $user->notify(new WelcomeMemberNotification);

        return response()->json([
            'message' => 'Willkommen als Vollmitglied! Du hast '.config()->integer('membership.bonus_tokens').' Bonus-Token erhalten.',
            'user' => new UserResource($user),
        ]);
    }
}

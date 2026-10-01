<?php

namespace App\Services;

use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\WelcomeMemberNotification;
use App\Notifications\WelcomeSupporterNotification;
use Illuminate\Support\Facades\DB;

/**
 * Activates a membership for a completed Stripe charge. Idempotent: the
 * confirm endpoint and the webhook may both call this for the same payment,
 * only the first one to acquire the row lock applies the membership.
 */
class MembershipPaymentCompleter
{
    public function __construct(private MembershipGrant $grant) {}

    /** @param  array<string, mixed>  $rawPayload */
    public function complete(MembershipPayment $payment, string $chargeId, array $rawPayload): MembershipPayment
    {
        $applied = false;

        $result = DB::transaction(function () use ($payment, $chargeId, $rawPayload, &$applied) {
            $locked = MembershipPayment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'COMPLETED') {
                return $locked ?? $payment;
            }

            $locked->update([
                'provider_charge_id' => $chargeId,
                'status' => 'COMPLETED',
                'payload' => $rawPayload,
                'captured_at' => now(),
            ]);

            $this->apply($locked, User::findOrFail($locked->user_id));
            $applied = true;

            return $locked;
        });

        if ($applied && $result->type !== MembershipPayment::TYPE_RENEWAL) {
            $user = User::findOrFail($result->user_id);
            $user->notify($result->type === MembershipPayment::TYPE_MEMBER
                ? new WelcomeMemberNotification
                : new WelcomeSupporterNotification);
        }

        return $result;
    }

    private function apply(MembershipPayment $payment, User $user): void
    {
        if ($payment->type === MembershipPayment::TYPE_RENEWAL) {
            $base = $user->membership_expires_at?->isFuture() ? $user->membership_expires_at : now();
            $user->membership_expires_at = $base->copy()->addYear();
            $user->renewal_reminder_sent_at = null;
            $user->save();

            if ($user->role === 'MEMBER') {
                $this->grant->grantMemberPackage($user);
            }

            return;
        }

        $user->role = $payment->type === MembershipPayment::TYPE_MEMBER ? 'MEMBER' : 'SUPPORTER';
        $user->membership_expires_at = now()->addYear();
        $user->street = $payment->street;
        $user->postal_code = $payment->postal_code;
        $user->city = $payment->city;
        $user->save();

        if ($user->role === 'MEMBER') {
            $this->grant->grantMemberPackage($user);
        }
    }
}

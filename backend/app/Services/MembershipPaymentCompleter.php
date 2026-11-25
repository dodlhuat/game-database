<?php

namespace App\Services;

use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\MembershipPaymentDuplicate;
use App\Notifications\MembershipPaymentDuplicateAdmin;
use App\Notifications\WelcomeMemberNotification;
use App\Notifications\WelcomeSupporterNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Activates a membership for a completed payment. Idempotent: the
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
        $duplicate = false;

        $result = DB::transaction(function () use ($payment, $chargeId, $rawPayload, &$applied, &$duplicate) {
            $locked = MembershipPayment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || ! in_array($locked->status, ['CREATED', 'FAILED'], true)) {
                return $locked ?? $payment;
            }

            $user = User::whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $fits = $this->stillFits($locked, $user);

            $locked->update([
                'provider_charge_id' => $chargeId,
                'status' => $fits ? 'COMPLETED' : MembershipPayment::STATUS_DUPLICATE,
                'payload' => $rawPayload,
                'captured_at' => now(),
            ]);

            if ($fits) {
                $this->apply($locked, $user);
                $applied = true;
            } else {
                $duplicate = true;
            }

            return $locked;
        });

        if ($duplicate) {
            $member = User::findOrFail($result->user_id);
            $member->notify(new MembershipPaymentDuplicate($result));
            Notification::send(User::where('role', 'ADMIN')->get(), new MembershipPaymentDuplicateAdmin($result, $member));
        }

        if ($applied && $result->type !== MembershipPayment::TYPE_RENEWAL) {
            $user = User::findOrFail($result->user_id);
            $user->notify($result->type === MembershipPayment::TYPE_MEMBER
                ? new WelcomeMemberNotification
                : new WelcomeSupporterNotification);
        }

        return $result;
    }

    /**
     * Whether the membership this payment was for is still due. A second
     * payment for the same thing — two tabs, a double click, a renewal paid
     * twice — no longer fits once the first one went through; it is then
     * refunded instead of applied (see PaymentService::sync). Mirrors the
     * checks in MembershipController::checkout.
     */
    private function stillFits(MembershipPayment $payment, User $user): bool
    {
        if ($payment->type !== MembershipPayment::TYPE_RENEWAL) {
            return $user->role === 'USER';
        }

        return in_array($user->role, ['MEMBER', 'SUPPORTER'], true)
            && $user->membership_expires_at !== null
            // a day of slack for a payment started right at the window's edge
            && $user->membership_expires_at->lte(now()->addMonths(3)->addDay());
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

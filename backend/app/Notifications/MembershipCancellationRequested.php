<?php

namespace App\Notifications;

use App\Models\MembershipCancellation;
use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the admins: a member cancelled and a refund may have to be transferred manually. */
class MembershipCancellationRequested extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private MembershipCancellation $cancellation, private User $member) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $c = $this->cancellation;

        return $this->buildFromTemplate('membership_cancellation_admin', [
            'name' => e($this->member->name),
            'email' => e($this->member->email),
            'refund' => self::euro($c->refund_cents),
            'refund_tokens' => $c->refund_tokens,
            'fee' => self::euro($c->fee_cents),
            'bonus_tokens' => $c->bonus_tokens_forfeited,
            'account_holder' => e($c->account_holder ?? '—'),
            'iban' => e($c->iban ?? '—'),
            'reason' => e($c->reason ?: '—'),
        ], config('frontend.url').'/admin/cancellations', $notifiable);
    }

    public static function euro(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }
}

<?php

namespace App\Notifications;

use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the admins: a membership was paid twice; the second payment has to be refunded by hand. */
class MembershipPaymentDuplicateAdmin extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private MembershipPayment $payment, private User $member) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->buildFromTemplate('membership_payment_duplicate_admin', [
            'name' => e($this->member->name),
            'email' => e($this->member->email),
            'amount' => MembershipCancellationRequested::euro($this->payment->price_cents),
            'date' => $this->payment->created_at?->format('d.m.Y H:i') ?? '',
            'payment_id' => e((string) $this->payment->provider_payment_id),
        ], config()->string('frontend.url').'/admin/cancellations', $notifiable);
    }
}

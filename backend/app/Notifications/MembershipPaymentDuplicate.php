<?php

namespace App\Notifications;

use App\Models\MembershipPayment;
use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the member: a second payment for the same membership came in and will be paid back. */
class MembershipPaymentDuplicate extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private MembershipPayment $payment) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->buildFromTemplate('membership_payment_duplicate', [
            'name' => e($notifiable->name),
            'amount' => MembershipCancellationRequested::euro($this->payment->price_cents),
            'date' => $this->payment->created_at?->format('d.m.Y') ?? '',
        ], config()->string('frontend.url').'/dashboard', $notifiable);
    }
}

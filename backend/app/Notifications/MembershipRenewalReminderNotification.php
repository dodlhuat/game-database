<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRenewalReminderNotification extends Notification
{
    use Queueable, UsesEmailTemplate;

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(\App\Models\User $notifiable): MailMessage
    {
        $renewalUrl = config('frontend.url') . '/dashboard';

        return $this->buildFromTemplate('membership_renewal_reminder', [
            'name'         => $notifiable->name,
            'expiry_date'  => $notifiable->membership_expires_at?->format('d.m.Y') ?? '',
            'renewal_link' => $renewalUrl,
        ], $renewalUrl, $notifiable);
    }
}

<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRenewalReminderNotification extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $renewalUrl = config('frontend.url', env('FRONTEND_URL', 'http://localhost:3000')) . '/dashboard';

        return $this->buildFromTemplate('membership_renewal_reminder', [
            'name'         => $notifiable->name,
            'expiry_date'  => $notifiable->membership_expires_at->format('d.m.Y'),
            'renewal_link' => $renewalUrl,
        ], $renewalUrl, $notifiable);
    }
}

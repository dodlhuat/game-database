<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeMemberNotification extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = config('frontend.url', env('FRONTEND_URL', 'http://localhost:3000')) . '/dashboard';

        return $this->buildFromTemplate('welcome_member', [
            'name' => $notifiable->name,
        ], $dashboardUrl, $notifiable);
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeMemberNotification extends Notification
{
    use Queueable, UsesEmailTemplate;

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $dashboardUrl = config('frontend.url').'/dashboard';

        return $this->buildFromTemplate('welcome_member', [
            'name' => $notifiable->name,
        ], $dashboardUrl, $notifiable);
    }
}

<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private string $token) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('frontend.url')
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->email);

        return $this->buildFromTemplate('password_reset', [
            'name'       => $notifiable->name,
            'reset_link' => $url,
        ], $url, $notifiable);
    }
}

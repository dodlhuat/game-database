<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'auth.verify-email',
            now()->addMinutes(60),
            ['id' => $notifiable->id]
        );

        return $this->buildFromTemplate('email_verification', [
            'name'              => $notifiable->name,
            'verification_link' => $verificationUrl,
        ], $verificationUrl, $notifiable);
    }
}

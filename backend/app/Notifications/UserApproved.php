<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApproved extends Notification
{
    use Queueable;

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $loginUrl = config('frontend.url', env('FRONTEND_URL', 'http://localhost:3000')) . '/login';

        return (new MailMessage)
            ->subject('Dein Konto wurde freigeschaltet!')
            ->greeting('Willkommen,')
            ->line('Dein Konto wurde von einem Administrator freigeschaltet.')
            ->action('Jetzt einloggen', $loginUrl)
            ->line('Viel Spaß beim Ausleihen!');
    }
}

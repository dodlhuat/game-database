<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRejected extends Notification
{
    use Queueable;

    public function __construct(private ?string $reason = null) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Deine Registrierung wurde abgelehnt')
            ->greeting('Hallo,')
            ->line('Leider wurde deine Registrierung abgelehnt.');

        if ($this->reason) {
            $message->line('**Grund:** ' . $this->reason);
        }

        return $message->line('Bei Fragen wende dich an uns.');
    }
}

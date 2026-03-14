<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(private User $newUser) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $approveUrl = config('app.url') . '/admin/users/' . $this->newUser->id;

        return (new MailMessage)
            ->subject('Neue Registrierung: ' . $this->newUser->name)
            ->greeting('Hallo Admin,')
            ->line('Ein neues Mitglied hat sich registriert und wartet auf Freischaltung.')
            ->line('**Name:** ' . $this->newUser->name)
            ->line('**E-Mail:** ' . $this->newUser->email)
            ->action('Mitglied freischalten', $approveUrl)
            ->line('Bitte prüfe die Anfrage im Admin-Bereich.');
    }
}

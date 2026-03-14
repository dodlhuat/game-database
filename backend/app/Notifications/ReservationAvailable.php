<?php

namespace App\Notifications;

use App\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationAvailable extends Notification
{
    use Queueable;

    public function __construct(private Game $game) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $catalogUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/games/' . $this->game->slug;

        return (new MailMessage)
            ->subject("„{$this->game->title}" ist jetzt verfügbar!")
            ->greeting('Gute Neuigkeiten!')
            ->line("Das Spiel **{$this->game->title}** ist jetzt wieder verfügbar.")
            ->action('Jetzt ausleihen', $catalogUrl)
            ->line('Bitte leihe es aus, bevor jemand anderes zugreift.');
    }
}

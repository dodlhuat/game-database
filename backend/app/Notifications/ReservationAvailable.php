<?php

namespace App\Notifications;

use App\Models\Game;
use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationAvailable extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Game $game) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $catalogUrl = config('frontend.url').'/games/'.$this->game->slug;

        return $this->buildFromTemplate('reservation_available', [
            'name' => $notifiable->name,
            'game' => $this->game->title,
        ], $catalogUrl, $notifiable);
    }
}

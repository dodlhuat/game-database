<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDueSoon extends Notification
{
    use Queueable;

    public function __construct(private Loan $loan) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $game = $this->loan->copy->game;
        $dueDate = $this->loan->due_date->format('d.m.Y');

        return (new MailMessage)
            ->subject("Erinnerung: Rückgabe von „{$game->title}" am {$dueDate}")
            ->greeting('Hallo,')
            ->line("Deine Ausleihe von **{$game->title}** läuft am **{$dueDate}** ab.")
            ->action('Zum Dashboard', env('FRONTEND_URL', 'http://localhost:3000') . '/dashboard')
            ->line('Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.');
    }
}

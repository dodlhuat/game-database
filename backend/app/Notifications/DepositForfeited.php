<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositForfeited extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Loan $loan, private ?string $notes = null) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $game         = $this->loan->copy->game;
        $dashboardUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/dashboard';

        return $this->buildFromTemplate('deposit_forfeited', [
            'name'    => $notifiable->name,
            'game'    => $game->title,
            'deposit' => $this->loan->deposit_tokens,
            'notes'   => $this->notes ?? '',
        ], $dashboardUrl, $notifiable);
    }
}

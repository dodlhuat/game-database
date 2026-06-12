<?php

namespace App\Notifications;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositForfeited extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Loan $loan, private ?string $notes = null) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        /** @var Copy $loanCopy */
        $loanCopy = $this->loan->copy;
        /** @var Game $game */
        $game = $loanCopy->game;
        $dashboardUrl = config('frontend.url').'/dashboard';

        return $this->buildFromTemplate('deposit_forfeited', [
            'name' => $notifiable->name,
            'game' => $game->title,
            'deposit' => $this->loan->deposit_tokens,
            'notes' => $this->notes ?? '',
        ], $dashboardUrl, $notifiable);
    }
}

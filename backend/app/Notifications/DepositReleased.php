<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositReleased extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Loan $loan) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(\App\Models\User $notifiable): MailMessage
    {
        /** @var \App\Models\Copy $loanCopy */
        $loanCopy     = $this->loan->copy;
        /** @var \App\Models\Game $game */
        $game         = $loanCopy->game;
        $dashboardUrl = config('frontend.url') . '/dashboard';

        return $this->buildFromTemplate('deposit_released', [
            'name'    => $notifiable->name,
            'game'    => $game->title,
            'deposit' => $this->loan->deposit_tokens,
        ], $dashboardUrl, $notifiable);
    }
}

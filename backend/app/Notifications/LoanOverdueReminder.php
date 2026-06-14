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

class LoanOverdueReminder extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Loan $loan) {}

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
        $dueDate = $this->loan->due_date->format('d.m.Y');
        $dashboardUrl = config('frontend.url').'/dashboard';

        return $this->buildFromTemplate('loan_overdue_reminder', [
            'name' => $notifiable->name,
            'game' => $game->title,
            'due_date' => $dueDate,
        ], $dashboardUrl, $notifiable);
    }
}

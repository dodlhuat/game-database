<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDueSoon extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Loan $loan) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $game    = $this->loan->copy->game;
        $dueDate = $this->loan->due_date->format('d.m.Y');
        $dashboardUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/dashboard';

        return $this->buildFromTemplate('loan_due_soon', [
            'name'     => $notifiable->name,
            'game'     => $game->title,
            'due_date' => $dueDate,
        ], $dashboardUrl);
    }
}

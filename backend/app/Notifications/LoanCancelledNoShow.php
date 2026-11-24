<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\User;
use App\Notifications\Concerns\DescribesLoanItem;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A pickup-event booking was not collected and has been cancelled and refunded. */
class LoanCancelledNoShow extends Notification
{
    use DescribesLoanItem, Queueable, UsesEmailTemplate;

    public function __construct(private Loan|PackageLoan $loan) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->buildFromTemplate('loan_cancelled_no_show', [
            'name' => e($notifiable->name),
            'game' => $this->itemTitle($this->loan),
            'pickup' => $this->pickupWhen($this->loan),
        ], config()->string('frontend.url').'/dashboard', $notifiable);
    }
}

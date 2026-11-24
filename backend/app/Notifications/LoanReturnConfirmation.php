<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\User;
use App\Notifications\Concerns\DescribesLoanItem;
use App\Notifications\Concerns\DescribesPickupLocation;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Receipt for the borrower: the return is booked, the check is still to come. */
class LoanReturnConfirmation extends Notification
{
    use DescribesLoanItem, DescribesPickupLocation, Queueable, UsesEmailTemplate;

    public function __construct(private Loan|PackageLoan $loan) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $loan = $this->loan;

        return $this->buildFromTemplate('loan_returned', [
            'name' => e($notifiable->name),
            'game' => $this->itemTitle($loan),
            'returned_at' => $loan->returned_at?->format('d.m.Y') ?? '',
            ...$this->locationVars($this->returnPlaceOf($loan)),
        ], config()->string('frontend.url').'/dashboard', $notifiable);
    }
}

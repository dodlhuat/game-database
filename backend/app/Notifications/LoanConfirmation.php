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
use Throwable;

/** Sent right after a game or package was borrowed: what, when and where. */
class LoanConfirmation extends Notification
{
    use DescribesLoanItem, DescribesPickupLocation, Queueable, UsesEmailTemplate;

    public function __construct(private Loan|PackageLoan $loan) {}

    /**
     * The loan is already booked when this runs — a mail hiccup must not
     * turn a successful loan into an error response.
     */
    public static function sendSafely(User $user, Loan|PackageLoan $loan): void
    {
        try {
            $user->notify(new self($loan));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $loan = $this->loan;

        return $this->buildFromTemplate('loan_confirmation', [
            'name' => $notifiable->name,
            'game' => $this->itemTitle($loan),
            'start_date' => $loan->start_date?->format('d.m.Y') ?? '',
            'due_date' => $loan->due_date->format('d.m.Y'),
            ...$this->locationVars($loan->pickupLocation),
        ], config()->string('frontend.url').'/dashboard', $notifiable);
    }
}

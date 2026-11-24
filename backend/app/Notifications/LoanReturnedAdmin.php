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

/** Sent to the admins when a member confirmed a return: something waits at a pickup location. */
class LoanReturnedAdmin extends Notification
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
        $loan = $this->loan;
        /** @var User $member */
        $member = $loan->user;
        $condition = $loan instanceof PackageLoan
            ? $loan->loans->first()?->return_condition
            : $loan->return_condition;

        return $this->buildFromTemplate('loan_returned_admin', [
            'member' => e($member->name),
            'game' => $this->itemTitle($loan),
            'location' => e($loan->pickupLocation->name ?? '—'),
            'condition' => $this->conditionLabel($condition),
            'returned_at' => $loan->returned_at?->format('d.m.Y H:i') ?? '',
        ], config()->string('frontend.url').'/admin/copy-review', $notifiable);
    }
}

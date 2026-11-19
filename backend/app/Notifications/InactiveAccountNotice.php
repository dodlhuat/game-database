<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InactiveAccountNotice extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private Carbon $deletionDate) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $loginUrl = config()->string('frontend.url').'/login';

        return $this->buildFromTemplate('inactive_account_notice', [
            'name' => $notifiable->name,
            'deletion_date' => $this->deletionDate->format('d.m.Y'),
            'login_link' => $loginUrl,
        ], $loginUrl, $notifiable);
    }
}

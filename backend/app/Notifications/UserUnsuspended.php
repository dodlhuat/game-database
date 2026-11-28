<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The suspension was lifted: the member can log in again. */
class UserUnsuspended extends Notification
{
    use Queueable, UsesEmailTemplate;

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->buildFromTemplate('user_unsuspended', [
            'name' => e($notifiable->name),
        ], config()->string('frontend.url').'/login', $notifiable);
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApproved extends Notification
{
    use Queueable, UsesEmailTemplate;

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $loginUrl = config('frontend.url').'/login';

        return $this->buildFromTemplate('user_approved', [
            'name' => $notifiable->name,
        ], $loginUrl, $notifiable);
    }
}

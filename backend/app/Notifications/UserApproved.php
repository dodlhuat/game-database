<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApproved extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = config('frontend.url', env('FRONTEND_URL', 'http://localhost:3000')) . '/login';

        return $this->buildFromTemplate('user_approved', [
            'name' => $notifiable->name,
        ], $loginUrl);
    }
}

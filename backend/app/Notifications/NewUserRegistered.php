<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private User $newUser) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approveUrl = config('frontend.url', env('FRONTEND_URL', 'http://localhost:3000')) . '/admin/users';

        return $this->buildFromTemplate('new_user_registered', [
            'name'  => $this->newUser->name,
            'email' => $this->newUser->email,
        ], $approveUrl, $notifiable);
    }
}

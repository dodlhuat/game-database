<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRejected extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private ?string $reason = null) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = $this->buildFromTemplate('user_rejected', [
            'name' => $notifiable->name,
        ], '', $notifiable);

        if ($this->reason) {
            $message->line('**Grund:** '.$this->reason);
        }

        return $message;
    }
}

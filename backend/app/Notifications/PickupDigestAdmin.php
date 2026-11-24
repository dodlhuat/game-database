<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Daily list for the admins: which returned games wait at which pickup location. */
class PickupDigestAdmin extends Notification
{
    use Queueable, UsesEmailTemplate;

    /**
     * @param  string  $pickupList  ready-made, escaped HTML grouped by location
     */
    public function __construct(private int $count, private string $pickupList) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->buildFromTemplate('pickup_digest_admin', [
            'count' => $this->count,
            'pickup_list' => $this->pickupList,
            'date' => now()->format('d.m.Y'),
        ], config()->string('frontend.url').'/admin/copy-review', $notifiable);
    }
}

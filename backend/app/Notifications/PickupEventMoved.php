<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use App\Notifications\Concerns\DescribesPickupLocation;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A pickup event the member booked has a new date or time. */
class PickupEventMoved extends Notification
{
    use DescribesPickupLocation, Queueable, UsesEmailTemplate;

    public function __construct(private Event $event, private string $previously) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->buildFromTemplate('pickup_event_moved', [
            'name' => e($notifiable->name),
            'previously' => e($this->previously),
            'pickup' => e(self::when($this->event)),
            ...$this->locationVars($this->event->pickupLocation),
        ], config()->string('frontend.url').'/dashboard', $notifiable);
    }

    /** "10.12.2026, 18:00–20:00 Uhr" */
    public static function when(Event $event): string
    {
        $span = substr($event->time ?? '', 0, 5).($event->end_time ? '–'.substr($event->end_time, 0, 5) : '');

        return $event->date->format('d.m.Y').($span !== '' ? ", {$span} Uhr" : '');
    }
}

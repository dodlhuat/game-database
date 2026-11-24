<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\Concerns\DescribesPickupLocation;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** The day before a personal pickup event: when, where and which games wait. */
class PickupEventReminder extends Notification
{
    use DescribesPickupLocation, Queueable, UsesEmailTemplate;

    /** @param  Collection<int, Loan>  $loans */
    public function __construct(private Event $event, private Collection $loans) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $event = $this->event;
        $time = substr($event->time ?? '', 0, 5).($event->end_time ? '–'.substr($event->end_time, 0, 5) : '');
        $games = $this->loans
            ->map(fn (Loan $l) => '<li>'.e($l->copy->game->title ?? '—').'</li>')
            ->implode('');

        return $this->buildFromTemplate('pickup_event_reminder', [
            'name' => e($notifiable->name),
            'date' => $event->date->format('d.m.Y'),
            'time' => $time !== '' ? "{$time} Uhr" : '',
            'games' => "<ul>{$games}</ul>",
            ...$this->locationVars($event->pickupLocation),
        ], config()->string('frontend.url').'/dashboard', $notifiable);
    }
}

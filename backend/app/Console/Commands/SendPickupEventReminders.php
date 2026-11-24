<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\PickupEventReminder;
use Illuminate\Console\Command;
use Throwable;

class SendPickupEventReminders extends Command
{
    protected $signature = 'loans:send-pickup-reminders';

    protected $description = 'Remind members the day before a personal pickup event, once per member and event';

    public function handle(): int
    {
        $events = Event::pickup()
            ->whereDate('date', today()->addDay())
            ->with('pickupLocation')
            ->get();

        $sent = 0;
        foreach ($events as $event) {
            // Package games carry the event too, so one query covers both
            $loans = Loan::where('pickup_event_id', $event->id)
                ->whereIn('status', Loan::OPEN_STATUSES)
                ->whereNull('handed_out_at')
                ->whereNull('pickup_reminder_sent_at')
                ->with(['user', 'copy.game'])
                ->get();

            foreach ($loans->groupBy('user_id') as $userLoans) {
                /** @var User $user */
                $user = $userLoans->first()?->user;
                try {
                    $user->notify(new PickupEventReminder($event, $userLoans->values()));
                    Loan::whereKey($userLoans->modelKeys())->update(['pickup_reminder_sent_at' => now()]);
                    $sent++;
                } catch (Throwable $e) {
                    // One broken address must not stop the remaining reminders
                    report($e);
                }
            }
        }

        $this->info("Sent {$sent} pickup reminder(s).");

        return self::SUCCESS;
    }
}

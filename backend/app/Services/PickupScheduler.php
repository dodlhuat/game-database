<?php

namespace App\Services;

use App\Models\Event;
use App\Models\LoanSetting;
use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Where and when a new loan is picked up: either at a regular location on
 * the next shared appointment, or at a pickup event, which brings its own
 * place, date and time.
 */
class PickupScheduler
{
    /**
     * Resolves the member's choice. Returns null when it is not a valid one
     * for this user (unknown, inactive, restricted, closed or full).
     *
     * @return array{location: PickupLocation, event: Event|null}|null
     */
    public function resolve(mixed $locationId, mixed $eventId, User $user, LoanSetting $setting): ?array
    {
        if ($eventId !== null && $eventId !== '') {
            $event = is_numeric($eventId)
                ? Event::bookableFor($user, $setting)->firstWhere('id', (int) $eventId)
                : null;
            if (! $event) {
                return null;
            }
            /** @var PickupLocation $location */
            $location = $event->pickupLocation;

            return ['location' => $location, 'event' => $event];
        }

        $location = PickupLocation::forNewLoan($locationId, $user);

        return $location ? ['location' => $location, 'event' => null] : null;
    }

    /**
     * Inside the booking transaction: locks the event row so two bookings at
     * once cannot overfill it, then re-checks the cap.
     */
    public function claimSpot(Event $event): void
    {
        /** @var Event $locked */
        $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
        if ($locked->spotsLeft() === 0) {
            throw new HttpException(409, 'Dieser Abholtermin ist inzwischen ausgebucht. Bitte wähle einen anderen.');
        }
    }

    public function startDate(?Event $event, LoanSetting $setting): Carbon
    {
        return $event ? $event->date->copy()->startOfDay() : $this->nextAppointment($setting);
    }

    /** The next shared appointment for regular locations, at least grace_days ahead. */
    public function nextAppointment(LoanSetting $setting): Carbon
    {
        $today = Carbon::today();
        $deadline = $today->copy()->addDays($setting->grace_days);
        $startDate = Carbon::parse($setting->start_date);

        if ($setting->interval_days <= 0) {
            return $deadline->copy()->addDay();
        }

        $daysSinceStart = (int) $startDate->diffInDays($deadline, false);
        $n = $daysSinceStart < 0 ? 0 : (int) floor($daysSinceStart / $setting->interval_days) + 1;

        return $startDate->copy()->addDays($n * $setting->interval_days);
    }
}

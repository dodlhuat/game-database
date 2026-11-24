<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Where a member may say they brought a game back. Regular loans go back to
 * where they were picked up. A game from a personal pickup event can come
 * back at a pickup event at the same place (one that has started since the
 * hand-out) or at any regular location open to the member.
 */
class ReturnPlaces
{
    /** @return array{locations: Collection<int, PickupLocation>, events: Collection<int, Event>} */
    public function optionsFor(Loan|PackageLoan $booking, User $user): array
    {
        if ($booking->pickup_event_id === null) {
            return [
                'locations' => collect([$booking->pickupLocation])->filter()->values(),
                'events' => collect(),
            ];
        }

        $since = ($booking->handed_out_at ?? $booking->start_date ?? today())->copy()->startOfDay();

        return [
            'locations' => PickupLocation::active()->regular()->accessibleTo($user)->ordered()->get(),
            'events' => Event::pickup()
                ->where('pickup_location_id', $booking->pickup_location_id)
                ->whereDate('date', '>=', $since)
                ->whereDate('date', '<=', today())
                ->with('pickupLocation')
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->get()
                // an event later today only counts once it has begun
                ->filter(fn (Event $e) => $e->startsAt()->lte(now()))
                ->values(),
        ];
    }

    /**
     * The member's choice, checked against the options. Without a choice a
     * regular loan goes back to its pickup place; an event loan must choose.
     *
     * @return array{location: PickupLocation|null, event: Event|null}|null
     */
    public function resolve(Loan|PackageLoan $booking, User $user, mixed $locationId, mixed $eventId): ?array
    {
        $options = $this->optionsFor($booking, $user);

        if (is_numeric($eventId)) {
            /** @var Event|null $event */
            $event = $options['events']->firstWhere('id', (int) $eventId);

            return $event && $event->pickupLocation ? ['location' => $event->pickupLocation, 'event' => $event] : null;
        }

        if (is_numeric($locationId)) {
            /** @var PickupLocation|null $location */
            $location = $options['locations']->firstWhere('id', (int) $locationId);

            return $location ? ['location' => $location, 'event' => null] : null;
        }

        // Regular loans (also legacy ones without a place) simply go back where they came from
        if ($booking->pickup_event_id === null) {
            return ['location' => $booking->pickupLocation, 'event' => null];
        }

        return null;
    }
}

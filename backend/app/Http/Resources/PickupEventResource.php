<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\LoanSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A bookable personal pickup event as the borrow dialog offers it: when,
 * where and how many places are left.
 *
 * @mixin Event
 */
class PickupEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date->format('Y-m-d'),
            'time' => $this->time ? substr($this->time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            /** @var int|null */
            'spots_left' => $this->spotsLeft($this->bookings_count),
            'booking_closes_at' => $this->bookingClosesAt(LoanSetting::forRequest($request))->toIso8601String(),
            'location' => new PickupLocationResource($this->whenLoaded('pickupLocation')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date->format('Y-m-d'),
            'time' => $this->time,
            'end_time' => $this->end_time,
            'is_all_day' => $this->is_all_day,
            // Personal pickup event: where it happens and how full it is
            'is_pickup' => $this->isPickup(),
            'pickup_location' => new PickupLocationResource($this->whenLoaded('pickupLocation')),
            'max_loans' => $this->max_loans,
            'bookings_count' => $this->when($this->isPickup(), fn () => $this->bookingsCount()),
            'description' => $this->description,
            'image_url' => $this->image_url,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Package;
use App\Models\PackageLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PackageLoan */
class PackageLoanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package' => $this->when($this->relationLoaded('package'), function () {
                /** @var Package $package */
                $package = $this->package;

                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'slug' => $package->slug,
                ];
            }),
            'user' => new UserResource($this->whenLoaded('user')),
            'pickup_location' => new PickupLocationResource($this->whenLoaded('pickupLocation')),
            // Booked for a pickup event and not handed out yet: no extending or returning
            'awaiting_pickup' => $this->isAwaitingPickup(),
            'handed_out_at' => $this->handed_out_at,
            // Personal pickup: the booked appointment with its time
            'pickup_event' => $this->whenLoaded('pickupEvent', fn () => $this->pickupEvent ? [
                'id' => $this->pickupEvent->id,
                'title' => $this->pickupEvent->title,
                'date' => $this->pickupEvent->date->format('Y-m-d'),
                'time' => $this->pickupEvent->time ? substr($this->pickupEvent->time, 0, 5) : null,
                'end_time' => $this->pickupEvent->end_time ? substr($this->pickupEvent->end_time, 0, 5) : null,
            ] : null),
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'returned_at' => $this->returned_at,
            'status' => $this->status,
            'is_overdue' => $this->due_date < now() && $this->status === 'ACTIVE',
            'loans' => LoanResource::collection($this->whenLoaded('loans')),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\PickupLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The return dialog's choices: regular locations and the pickup events a
 * game may have been handed back at.
 *
 * @property array{locations: Collection<int, PickupLocation>, events: Collection<int, Event>} $resource
 */
class ReturnPlacesResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'locations' => PickupLocationResource::collection($this->resource['locations']),
            'events' => $this->resource['events']->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'date' => $e->date->format('Y-m-d'),
                'time' => $e->time ? substr($e->time, 0, 5) : null,
                'end_time' => $e->end_time ? substr($e->end_time, 0, 5) : null,
                'location' => $e->pickupLocation ? new PickupLocationResource($e->pickupLocation) : null,
            ])->values(),
        ];
    }
}

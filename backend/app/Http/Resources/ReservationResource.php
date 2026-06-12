<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reservation */
class ReservationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game' => new GameResource($this->whenLoaded('game')),
            'user' => new UserResource($this->whenLoaded('user')),
            'position' => $this->position,
            'notified_at' => $this->notified_at,
            'created_at' => $this->created_at,
        ];
    }
}

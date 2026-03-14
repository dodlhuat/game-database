<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'game'         => new GameResource($this->whenLoaded('game')),
            'user'         => new UserResource($this->whenLoaded('user')),
            'position'     => $this->position,
            'notified_at'  => $this->notified_at,
            'created_at'   => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'game_id'   => $this->game_id,
            'game'      => $this->whenLoaded('game', fn() => [
                'id'    => $this->game->id,
                'title' => $this->game->title,
            ]),
            'condition' => $this->condition,
            'qr_code'   => $this->qr_code,
            'notes'     => $this->notes,
            'is_available' => $this->when(
                $this->relationLoaded('activeLoans'),
                fn() => $this->activeLoans->isEmpty() && $this->condition !== 'LOCKED'
            ),
        ];
    }
}

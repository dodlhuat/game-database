<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Copy */
class CopyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'game_id'      => $this->game_id,
            'game'         => $this->whenLoaded('game', function () {
                /** @var \App\Models\Game $game */
                $game = $this->game;
                return [
                    'id'    => $game->id,
                    'title' => $game->title,
                ];
            }),
            'condition'    => $this->condition,
            'borrow_count' => $this->borrow_count,
            'qr_code'      => $this->qr_code,
            'notes'        => $this->notes,
            'is_available' => $this->when(
                $this->relationLoaded('activeLoans'),
                fn() => $this->activeLoans->isEmpty()
                    && !in_array($this->condition, ['LOCKED', 'REVIEW', 'DAMAGED'])
            ),
        ];
    }
}

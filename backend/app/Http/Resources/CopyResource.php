<?php

namespace App\Http\Resources;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Copy */
class CopyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'game' => $this->whenLoaded('game', function () {
                /** @var Game $game */
                $game = $this->game;

                return [
                    'id' => $game->id,
                    'title' => $game->title,
                ];
            }),
            'condition' => $this->condition,
            'borrow_count' => $this->borrow_count,
            'qr_code' => $this->qr_code,
            'owner' => $this->when($request->user()?->isAdmin() ?? false, $this->owner),
            'notes' => $this->notes,
            'last_return' => $this->whenLoaded('lastReturnedLoan', function () {
                /** @var Loan|null $loan */
                $loan = $this->lastReturnedLoan;

                return $loan ? [
                    'return_condition' => $loan->return_condition,
                    'returned_at' => $loan->returned_at,
                    'user_name' => $loan->user?->name,
                ] : null;
            }),
            'is_available' => $this->when(
                $this->relationLoaded('activeLoans'),
                fn () => $this->activeLoans->isEmpty()
                    && ! in_array($this->condition, ['LOCKED', 'REVIEW', 'DAMAGED'])
            ),
        ];
    }
}

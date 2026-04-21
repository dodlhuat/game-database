<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'copy'             => new CopyResource($this->whenLoaded('copy')),
            'game'             => $this->when(
                $this->relationLoaded('copy') && $this->copy?->relationLoaded('game'),
                fn() => $this->copy->game ? new GameResource($this->copy->game) : null
            ),
            'user'             => new UserResource($this->whenLoaded('user')),
            'start_date'       => $this->start_date,
            'due_date'         => $this->due_date,
            'returned_at'      => $this->returned_at,
            'return_condition' => $this->return_condition,
            'deposit_tokens'   => $this->deposit_tokens,
            'status'           => $this->status,
            'is_overdue'       => $this->due_date < now() && in_array($this->status, ['ACTIVE', 'EXTENDED']),
            'extensions'       => ExtensionResource::collection($this->whenLoaded('extensions')),
            'created_at'       => $this->created_at,
        ];
    }
}

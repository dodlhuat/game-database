<?php

namespace App\Http\Resources;

use App\Models\Copy;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Loan */
class LoanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'copy' => new CopyResource($this->whenLoaded('copy')),
            'game' => $this->when(
                $this->relationLoaded('copy') && $this->copy?->relationLoaded('game'),
                function () {
                    /** @var Copy $copy */
                    $copy = $this->copy;

                    return $copy->game !== null ? new GameResource($copy->game) : null;
                }
            ),
            'user' => new UserResource($this->whenLoaded('user')),
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'returned_at' => $this->returned_at,
            'return_condition' => $this->return_condition,
            'deposit_tokens' => $this->deposit_tokens,
            'status' => $this->status,
            'is_overdue' => $this->due_date < now() && in_array($this->status, ['ACTIVE', 'EXTENDED']),
            'overdue_reminder_sent_at' => $this->overdue_reminder_sent_at,
            'extensions' => ExtensionResource::collection($this->whenLoaded('extensions')),
            'created_at' => $this->created_at,
        ];
    }
}

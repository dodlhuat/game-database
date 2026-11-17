<?php

namespace App\Http\Resources;

use App\Models\PickupLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PickupLocation */
class PickupLocationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'opening_hours' => $this->opening_hours,
            'notes' => $this->notes,
            'map_url' => $this->map_url,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            // Admin list only: borrowed games that still have to come back here.
            'open_loans_count' => $this->whenCounted('open_loans'),
        ];
    }
}

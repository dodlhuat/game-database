<?php

namespace App\Http\Resources;

use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Mechanic */
class MechanicResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'games_count' => $this->whenCounted('games'),
        ];
    }
}

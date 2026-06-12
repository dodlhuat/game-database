<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon_url' => $this->icon_url,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'games_count' => $this->whenCounted('games'),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}

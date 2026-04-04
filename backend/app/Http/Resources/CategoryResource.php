<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'icon_url'    => $this->icon_url,
            'sort_order'  => $this->sort_order,
            'parent_id'   => $this->parent_id,
            'is_active'   => $this->is_active,
            'games_count' => $this->whenCounted('games'),
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'slug'              => $this->slug,
            'description'       => $this->description,
            'short_description' => $this->short_description,
            'category'          => new CategoryResource($this->whenLoaded('category')),
            'tags'              => TagResource::collection($this->whenLoaded('tags')),
            'min_players'       => $this->min_players,
            'max_players'       => $this->max_players,
            'min_age'           => $this->min_age,
            'duration_min'      => $this->duration_min,
            'duration_max'      => $this->duration_max,
            'difficulty'        => $this->difficulty,
            'languages'         => $this->whenLoaded('languages', fn() => $this->languages->map(fn($l) => ['id' => $l->id, 'name' => $l->name])->values()),
            'year'              => $this->year,
            'instagram_url'     => $this->instagram_url,
            'deposit_tokens'    => $this->deposit_tokens,
            'cover_image_url' => $this->cover_image_url,
            'is_active'       => $this->is_active,
            'available_copies_count' => $this->whenCounted('available_copies_count', $this->available_copies_count ?? 0),
            'copies_count'          => $this->whenCounted('copies', $this->copies_count ?? 0),
            'avg_rating'      => $this->whenCounted('reviews') ? null : $this->when(
                $this->relationLoaded('reviews'),
                fn() => $this->reviews->avg('rating')
            ),
            'reviews_count'   => $this->whenCounted('reviews'),
            'is_favorited'      => $this->when(isset($this->is_favorited), $this->is_favorited),
            'already_borrowed'  => $this->when(isset($this->already_borrowed), $this->already_borrowed),
            'images'                => $this->whenLoaded('images', fn() => $this->images->map(fn($img) => ['id' => $img->id, 'url' => $img->url])),
            'copies'                => CopyResource::collection($this->whenLoaded('copies')),
            'earliest_available_at' => $this->when(
                $this->relationLoaded('copies'),
                fn() => $this->copies
                    ->flatMap(fn($copy) => $copy->activeLoans ?? collect())
                    ->whereIn('status', ['ACTIVE', 'EXTENDED'])
                    ->min('due_date')
            ),
            'created_at'      => $this->created_at,
        ];
    }
}

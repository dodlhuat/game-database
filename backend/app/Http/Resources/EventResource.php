<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date->format('Y-m-d'),
            'time' => $this->time,
            'is_all_day' => $this->is_all_day,
            'description' => $this->description,
            'image_url' => $this->image_url,
        ];
    }
}

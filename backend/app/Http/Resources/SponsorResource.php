<?php

namespace App\Http\Resources;

use App\Models\Sponsor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sponsor */
class SponsorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'logo_url' => $this->logo_url,
            'logo_on_light' => $this->logo_on_light,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}

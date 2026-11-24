<?php

namespace App\Http\Resources;

use App\Models\PickupLocation;
use App\Models\User;
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
            // Personal pickup: only offered through pickup events held there
            'is_personal' => $this->is_personal,
            'sort_order' => $this->sort_order,
            // Admin list only: borrowed games that still have to come back here.
            'open_loans_count' => $this->whenCounted('open_loans'),
            // Admin list only: who the location is restricted to — empty means everyone.
            'users' => $this->whenLoaded('users', fn () => $this->users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                // Flags assignments that no longer matter, so admins can tidy up
                'can_borrow' => $u->isAdmin() || $u->isMember(),
            ])->values()),
        ];
    }
}

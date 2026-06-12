<?php

namespace App\Http\Resources;

use App\Models\Package;
use App\Models\PackageLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PackageLoan */
class PackageLoanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package' => $this->when($this->relationLoaded('package'), function () {
                /** @var Package $package */
                $package = $this->package;

                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'slug' => $package->slug,
                ];
            }),
            'user' => new UserResource($this->whenLoaded('user')),
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'returned_at' => $this->returned_at,
            'status' => $this->status,
            'is_overdue' => $this->due_date < now() && $this->status === 'ACTIVE',
            'loans' => LoanResource::collection($this->whenLoaded('loans')),
            'created_at' => $this->created_at,
        ];
    }
}

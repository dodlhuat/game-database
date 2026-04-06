<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageLoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'package'     => $this->when($this->relationLoaded('package'), fn() => [
                'id'   => $this->package->id,
                'name' => $this->package->name,
                'slug' => $this->package->slug,
            ]),
            'user'        => new UserResource($this->whenLoaded('user')),
            'start_date'  => $this->start_date,
            'due_date'    => $this->due_date,
            'returned_at' => $this->returned_at,
            'status'      => $this->status,
            'is_overdue'  => $this->due_date < now() && $this->status === 'ACTIVE',
            'loans'       => LoanResource::collection($this->whenLoaded('loans')),
            'created_at'  => $this->created_at,
        ];
    }
}

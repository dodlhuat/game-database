<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtensionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'loan_id'            => $this->loan_id,
            'requested_at'       => $this->requested_at,
            'requested_due_date' => $this->requested_due_date,
            'status'             => $this->status,
            'admin_note'         => $this->admin_note,
        ];
    }
}

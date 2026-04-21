<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'email'                  => $this->email,
            'address'                => $this->address,
            'role'                   => $this->role,
            'status'                 => $this->status,
            'newsletter_opt_in'      => $this->newsletter_opt_in,
            'terms_accepted_at'      => $this->terms_accepted_at,
            'terms_version'          => $this->terms_version,
            'tokens'                 => $this->tokens,
            'tokens_blocked'         => $this->tokens_blocked,
            'membership_expires_at'  => $this->membership_expires_at,
            'is_member'              => $this->isMember(),
            'email_verified_at'      => $this->email_verified_at,
            'created_at'             => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PickupLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'opening_hours' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'map_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_active' => ['boolean'],
            // Personal pickup: only offered through pickup events held there
            'is_personal' => ['boolean'],
            // Restrict the location to these users; an empty list opens it for everyone
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}

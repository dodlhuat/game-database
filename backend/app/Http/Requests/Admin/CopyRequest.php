<?php

namespace App\Http\Requests\Admin;

use App\Models\Copy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $route = $this->route('copy');
        $copyId = $route instanceof Copy ? $route->id : null;

        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'condition' => ['required', 'in:NEW,VERY_GOOD,GOOD,REVIEW,DAMAGED,LOCKED'],
            'qr_code' => ['nullable', 'string', Rule::unique('copies', 'qr_code')->ignore($copyId)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

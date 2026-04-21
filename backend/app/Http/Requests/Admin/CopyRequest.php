<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $copyId = $this->route('copy')?->id;

        return [
            'game_id'   => ['required', 'integer', 'exists:games,id'],
            'condition' => ['required', 'in:NEW,VERY_GOOD,GOOD,REVIEW,DAMAGED,LOCKED'],
            'qr_code'   => ['nullable', 'string', Rule::unique('copies', 'qr_code')->ignore($copyId)],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'date'        => ['required', 'date_format:Y-m-d'],
            'time'        => ['nullable', 'date_format:H:i'],
            'is_all_day'  => ['boolean'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'max:5120'],
        ];
    }
}

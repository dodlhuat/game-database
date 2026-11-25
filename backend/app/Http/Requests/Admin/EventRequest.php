<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'is_all_day' => ['boolean'],
            'time' => ['nullable', 'date_format:H:i', 'required_unless:is_all_day,1'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_unless:is_all_day,1', 'after:time'],
            // Set = personal pickup event at this location, bookable when borrowing
            'pickup_location_id' => ['nullable', 'integer', Rule::exists('pickup_locations', 'id')->whereNull('deleted_at')->where('is_personal', true)],
            'max_loans' => ['nullable', 'integer', 'min:1', 'max:999'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            // "Remove image" in the admin form (ignored when a new image comes along)
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * A pickup event is an appointment: it needs a start and an end, not a whole day.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('pickup_location_id') && $this->boolean('is_all_day')) {
                    $validator->errors()->add('is_all_day', 'Ein Abholtermin braucht Beginn und Ende und kann nicht ganztägig sein.');
                }
            },
        ];
    }
}

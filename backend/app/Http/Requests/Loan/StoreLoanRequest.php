<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'copy_id' => ['required', 'integer', 'exists:copies,id'],
            // Optional only while a single location is active — then it is implied.
            'pickup_location_id' => ['nullable', 'integer'],
        ];
    }
}

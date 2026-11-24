<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class ReturnLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'return_condition' => ['required', 'in:GOOD,WORN,DAMAGED'],
            // Where it came back (checked against the loan's options); one of them
            'return_location_id' => ['nullable', 'integer'],
            'return_event_id' => ['nullable', 'integer'],
        ];
    }
}

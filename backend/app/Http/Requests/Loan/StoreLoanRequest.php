<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'copy_id'    => ['required', 'integer', 'exists:copies,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'due_date'   => ['required', 'date', 'after:start_date'],
        ];
    }
}

<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxDate = now()->addWeeks(2)->toDateString();

        return [
            'requested_due_date' => ['required', 'date', 'after:today', "before_or_equal:{$maxDate}"],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_due_date.before_or_equal' => 'Das Rückgabedatum darf maximal 2 Wochen in der Zukunft liegen.',
        ];
    }
}

<?php

namespace App\Http\Requests\Loan;

use App\Models\Loan;
use App\Services\ExtensionPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreExtensionRequest extends FormRequest
{
    private const MAX_WEEKS = 2;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $loan = $this->route('loan');
        $base = $loan instanceof Loan ? ExtensionPolicy::earliestBase($loan) : today();

        return [
            'requested_due_date' => [
                'required',
                'date',
                'after:'.$base->toDateString(),
                'before_or_equal:'.$base->copy()->addWeeks(self::MAX_WEEKS)->toDateString(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_due_date.after' => 'Das neue Rückgabedatum muss nach dem aktuellen liegen.',
            'requested_due_date.before_or_equal' => 'Eine Verlängerung ist um maximal 2 Wochen möglich.',
        ];
    }
}

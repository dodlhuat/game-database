<?php

namespace App\Http\Requests\Loan;

use App\Models\Loan;
use App\Models\LoanSetting;
use App\Services\ExtensionPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $loan = $this->route('loan');
        $setting = LoanSetting::forRequest($this);
        $base = $loan instanceof Loan ? ExtensionPolicy::earliestBase($loan) : today();
        $latest = $loan instanceof Loan
            ? ExtensionPolicy::latestDueDate($loan, $setting)
            : today()->addWeeks($setting->loan_duration_weeks);

        return [
            'requested_due_date' => [
                'required',
                'date',
                'after:'.$base->toDateString(),
                'before_or_equal:'.$latest->toDateString(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_due_date.after' => 'Das neue Rückgabedatum muss nach dem aktuellen liegen.',
            'requested_due_date.before_or_equal' => 'Eine Verlängerung ist um maximal '
                .LoanSetting::forRequest($this)->loan_duration_weeks.' Wochen möglich.',
        ];
    }
}

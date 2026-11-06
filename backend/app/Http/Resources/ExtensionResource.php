<?php

namespace App\Http\Resources;

use App\Models\Extension;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Services\ExtensionPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Extension */
class ExtensionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'requested_at' => $this->requested_at,
            'requested_due_date' => $this->requested_due_date,
            'status' => $this->status,
            'tokens_charged' => $this->tokens_charged,
            'admin_note' => $this->admin_note,
            'loan' => new LoanResource($this->whenLoaded('loan')),
            'next_cost' => $this->when($this->hasLoanDetails(), fn () => $this->loanPolicyValue(
                fn (ExtensionPolicy $policy, Loan $loan) => $policy->nextCost($loan, LoanSetting::forRequest($request))
            )),
            'game_reserved' => $this->when($this->hasLoanDetails(), fn () => $this->loanPolicyValue(
                fn (ExtensionPolicy $policy, Loan $loan) => $policy->isReservedByOthers($loan)
            )),
        ];
    }

    private function hasLoanDetails(): bool
    {
        return $this->relationLoaded('loan') && $this->loan?->relationLoaded('extensions') === true;
    }

    /**
     * @template T
     *
     * @param  callable(ExtensionPolicy, Loan): T  $read
     * @return T
     */
    private function loanPolicyValue(callable $read): mixed
    {
        /** @var Loan $loan */
        $loan = $this->loan;

        return $read(app(ExtensionPolicy::class), $loan);
    }
}

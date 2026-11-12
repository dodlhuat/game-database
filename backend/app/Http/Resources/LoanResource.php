<?php

namespace App\Http\Resources;

use App\Models\Copy;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\User;
use App\Services\ExtensionPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/** @mixin Loan */
class LoanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $hasExtensions = $this->relationLoaded('extensions');

        return [
            'id' => $this->id,
            'copy' => new CopyResource($this->whenLoaded('copy')),
            'game' => $this->when(
                $this->relationLoaded('copy') && $this->copy?->relationLoaded('game'),
                function () {
                    /** @var Copy $copy */
                    $copy = $this->copy;

                    return $copy->game !== null ? new GameResource($copy->game) : null;
                }
            ),
            'user' => new UserResource($this->whenLoaded('user')),
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'returned_at' => $this->returned_at,
            'return_condition' => $this->return_condition,
            'deposit_tokens' => $this->deposit_tokens,
            'status' => $this->status,
            'is_overdue' => $this->due_date < now() && in_array($this->status, ['ACTIVE', 'EXTENDED']),
            'overdue_reminder_sent_at' => $this->overdue_reminder_sent_at,
            'extensions' => ExtensionResource::collection($this->whenLoaded('extensions')),
            'can_extend' => $this->when($hasExtensions, fn () => $this->extensionBlockReason($request) === null),
            'extension_blocked_reason' => $this->when($hasExtensions, fn () => $this->extensionBlockReason($request)),
            'next_extension_cost' => $this->when($hasExtensions, fn () => $this->nextExtensionCost($request)),
            'extensions_used' => $this->when($hasExtensions, fn () => $this->extensionsUsed()),
            'max_extensions' => $this->when($hasExtensions, fn () => $this->maxExtensions($request)),
            'extension_earliest_due_date' => $this->when($hasExtensions, fn () => $this->extensionEarliestDueDate()),
            'extension_latest_due_date' => $this->when($hasExtensions, fn () => $this->extensionLatestDueDate($request)),
            'created_at' => $this->created_at,
        ];
    }

    private function extensionsUsed(): int
    {
        return app(ExtensionPolicy::class)->used($this->loan());
    }

    private function maxExtensions(Request $request): int
    {
        return LoanSetting::forRequest($request)->max_extensions;
    }

    /** First date the extension endpoint accepts, so the date picker never offers more. */
    private function extensionEarliestDueDate(): string
    {
        return ExtensionPolicy::earliestBase($this->loan())->addDay()->toDateString();
    }

    /** Last date the extension endpoint accepts. */
    private function extensionLatestDueDate(Request $request): string
    {
        return ExtensionPolicy::latestDueDate($this->loan(), LoanSetting::forRequest($request))->toDateString();
    }

    private function loan(): Loan
    {
        $loan = $this->resource;
        if (! $loan instanceof Loan) {
            throw new LogicException('LoanResource wraps a '.get_debug_type($loan).' instead of a Loan.');
        }

        return $loan;
    }

    private function extensionBlockReason(Request $request): ?string
    {
        /** @var Loan $loan */
        $loan = $this->resource;

        return app(ExtensionPolicy::class)->blockReason($loan, LoanSetting::forRequest($request));
    }

    private function nextExtensionCost(Request $request): int
    {
        /** @var Loan $loan */
        $loan = $this->resource;
        $user = $request->user();

        if ($user instanceof User && $user->isAdmin()) {
            return 0;
        }

        return app(ExtensionPolicy::class)->nextCost($loan, LoanSetting::forRequest($request));
    }
}

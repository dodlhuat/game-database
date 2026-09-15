<?php

namespace App\Http\Resources;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\TokenTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TokenTransaction */
class TokenTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'loan' => $this->when($this->relationLoaded('loan') && $this->loan, function () {
                /** @var Loan $loan */
                $loan = $this->loan;

                return [
                    'copy' => $this->when($loan->relationLoaded('copy') && $loan->copy, function () use ($loan) {
                        /** @var Copy $copy */
                        $copy = $loan->copy;

                        return [
                            'game' => $this->when(
                                $copy->relationLoaded('game') && $copy->game,
                                function () use ($copy) {
                                    /** @var Game $game */
                                    $game = $copy->game;

                                    return [
                                        'title' => $game->title,
                                        'slug' => $game->slug,
                                    ];
                                }
                            ),
                        ];
                    }),
                ];
            }),
        ];
    }
}

<?php

namespace App\Services\SmartSearch;

use App\Models\Game;
use Illuminate\Support\Collection;

class SimilarityScorer
{
    /**
     * Score and rank $candidates by similarity to $reference.
     * Returns a new Collection sorted by score (highest first), excluding $reference itself.
     * Games with a score of 0 are omitted.
     *
     * @param  Collection<int, Game>  $candidates
     * @return Collection<int, Game>
     */
    public function score(Game $reference, Collection $candidates): Collection
    {
        /** @var array<int> $refTagIds */
        $refTagIds = $reference->tags->pluck('id')->toArray();

        return $candidates
            ->filter(fn (Game $game): bool => $game->id !== $reference->id)
            ->map(function (Game $game) use ($reference, $refTagIds): Game {
                $s = 0;

                if ($reference->category_id !== null && $game->category_id === $reference->category_id) {
                    $s += 5;
                }

                $s += $game->tags->pluck('id')->intersect($refTagIds)->count() * 3;

                if ($reference->min_players !== null && $reference->max_players !== null
                    && $game->min_players !== null && $game->max_players !== null) {
                    $overlap = min($reference->max_players, $game->max_players)
                        - max($reference->min_players, $game->min_players);
                    if ($overlap >= 0) {
                        $s += 2;
                    }
                }

                if ($reference->duration_min !== null && $game->duration_min !== null && $reference->duration_min > 0) {
                    $ratio = $game->duration_min / $reference->duration_min;
                    if ($ratio >= 0.5 && $ratio <= 1.5) {
                        $s += 1;
                    }
                }

                if ($reference->min_age !== null && $game->min_age !== null
                    && abs($game->min_age - $reference->min_age) <= 3) {
                    $s += 1;
                }

                $game->setAttribute('similarity_score', $s);

                return $game;
            })
            ->filter(fn (Game $game): bool => (int) $game->getAttribute('similarity_score') > 0)
            ->sortByDesc(fn (Game $game): int => (int) $game->getAttribute('similarity_score'))
            ->values();
    }
}

<?php

namespace App\Support;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The filters of the game list (mechanic, tag, difficulty, players,
 * duration, language, age, availability), in one place so the regular list
 * and the smart search narrow results the same way.
 */
class GameFilters
{
    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public static function apply(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->string('mechanic')->toString(), function ($q, string $value) {
                $slugs = array_filter(explode(',', $value));
                $q->whereHas('mechanics', fn ($q) => $q->whereIn('slug', $slugs));
            })
            ->when($request->string('tag')->toString(), fn ($q, string $slug) => $q->whereHas('tags', fn ($q) => $q->where('slug', $slug))
            )
            ->when($request->string('difficulty')->toString(), fn ($q, string $diff) => $q->where('difficulty', $diff))
            // "4" = playable with exactly 4; "6+" = playable with 6 or more
            ->when(self::players($request), function ($q, array $players) {
                [$n, $orMore] = $players;
                if (! $orMore) {
                    $q->where(fn ($q) => $q->whereNull('min_players')->orWhere('min_players', '<=', $n));
                }
                $q->where(fn ($q) => $q->whereNull('max_players')->orWhere('max_players', '>=', $n));
            })
            ->when($request->string('duration')->toString(), function ($q, string $dur) {
                if ($dur === 'short') {
                    $q->whereNotNull('duration_min')->where('duration_min', '<=', 30);
                }
                if ($dur === 'medium') {
                    $q->whereNotNull('duration_min')->whereBetween('duration_min', [31, 90]);
                }
                if ($dur === 'long') {
                    $q->whereNotNull('duration_min')->where('duration_min', '>', 90);
                }
            })
            ->when($request->integer('language'), fn ($q, int $langId) => $q->whereHas('languages', fn ($q) => $q->where('languages.id', $langId))
            )
            ->when($request->filled('min_age_from') || $request->filled('min_age_to'), function ($q) use ($request) {
                $from = $request->filled('min_age_from') ? $request->integer('min_age_from') : null;
                $to = $request->filled('min_age_to') ? $request->integer('min_age_to') : null;
                $q->where(function ($q) use ($from, $to) {
                    $q->whereNull('min_age')->orWhere(function ($q) use ($from, $to) {
                        if ($from !== null) {
                            $q->where('min_age', '>=', $from);
                        }
                        if ($to !== null) {
                            $q->where('min_age', '<=', $to);
                        }
                    });
                });
            })
            ->when($request->boolean('available'), fn ($q) => $q->whereHas('copies', fn ($q) => $q->whereNotIn('condition', ['LOCKED', 'REVIEW', 'DAMAGED'])->whereDoesntHave('activeLoans')
            )
            );
    }

    /**
     * The player count asked for, and whether more is fine too ("6+").
     * Empty, zero or anything that is not a count means no filter.
     *
     * @return array{int, bool}|null
     */
    private static function players(Request $request): ?array
    {
        if (preg_match('/^\s*(\d+)(\+?)\s*$/', $request->string('players')->toString(), $m) !== 1) {
            return null;
        }
        $n = (int) $m[1];

        return $n > 0 ? [$n, $m[2] === '+'] : null;
    }
}

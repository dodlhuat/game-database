<?php

namespace App\Services\SmartSearch;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;

class QueryParser
{
    /** @return array{intent: string, reference?: string, slug?: string, query?: string} */
    public function parse(string $query): array
    {
        $query = trim($query);

        if (preg_match('/^(?:ähnliche?\s+)?spiele?\s+wie\s+(.+)$/iu', $query, $matches)) {
            return ['intent' => 'SIMILARITY', 'reference' => trim($matches[1])];
        }

        $normalized = $this->normalize($query);

        $catSlug = $this->bestCategoryMatch($normalized);
        if ($catSlug !== null) {
            return ['intent' => 'CATEGORY', 'slug' => $catSlug];
        }

        $tagSlug = $this->bestTagMatch($normalized);
        if ($tagSlug !== null) {
            return ['intent' => 'TAG', 'slug' => $tagSlug];
        }

        return ['intent' => 'FULLTEXT', 'query' => $query];
    }

    public function findGameByTitle(string $title): ?Game
    {
        $normalized = $this->normalize($title);
        $best = null;
        $bestScore = 0.0;

        Game::where('is_active', true)->get(['id', 'title', 'slug'])->each(
            function (Game $game) use ($normalized, &$best, &$bestScore): void {
                $score = $this->similarity($normalized, $this->normalize($game->title));
                if ($score > $bestScore && $score >= 0.65) {
                    $bestScore = $score;
                    $best = $game;
                }
            }
        );

        return $best;
    }

    private function bestCategoryMatch(string $normalized): ?string
    {
        $best = null;
        $bestScore = 0.0;

        Category::all(['name', 'slug'])->each(
            function (Category $cat) use ($normalized, &$best, &$bestScore): void {
                $score = $this->similarity($normalized, $this->normalize($cat->name));
                if ($score > $bestScore && $score >= 0.75) {
                    $bestScore = $score;
                    $best = $cat->slug;
                }
            }
        );

        return $best;
    }

    private function bestTagMatch(string $normalized): ?string
    {
        // Also try without trailing German adjective ending ("lustige" → "lustig")
        $stem = rtrim($normalized, 'e');
        $best = null;
        $bestScore = 0.0;

        Tag::all(['name', 'slug'])->each(
            function (Tag $tag) use ($normalized, $stem, &$best, &$bestScore): void {
                $tagNorm = $this->normalize($tag->name);
                $score = max(
                    $this->similarity($normalized, $tagNorm),
                    $this->similarity($stem, $tagNorm)
                );
                if ($score > $bestScore && $score >= 0.70) {
                    $bestScore = $score;
                    $best = $tag->slug;
                }
            }
        );

        return $best;
    }

    public function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/[^a-z0-9\s]/', '', $s) ?? $s;

        return trim($s);
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }
        if (str_contains($b, $a) || str_contains($a, $b)) {
            return 0.9;
        }
        similar_text($a, $b, $percent);

        return $percent / 100.0;
    }
}

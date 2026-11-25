<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A slug from a name that is free in the given table: "familie" taken →
 * "familie-2", and so on. Different names can slug to the same text
 * ("Deck-Building" / "Deck Building"), which the unique column would
 * otherwise turn into a server error.
 */
class UniqueSlug
{
    /** @param  class-string<Model>  $model */
    public static function for(string $model, string $name, ?int $ignoreId = null, string $fallback = 'eintrag'): string
    {
        $base = Str::slug($name) ?: $fallback;
        $slug = $base;
        for ($i = 2; self::taken($model, $slug, $ignoreId); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /** @param  class-string<Model>  $model */
    private static function taken(string $model, string $slug, ?int $ignoreId): bool
    {
        return $model::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}

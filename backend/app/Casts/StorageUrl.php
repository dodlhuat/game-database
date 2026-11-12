<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Uploaded files are stored as a path on the public disk (e.g. "covers/a.jpg")
 * and turned into a full URL when read, so the stored value never depends on
 * the domain the app ran on at upload time. Values that already are absolute
 * URLs (demo data, external images) pass through unchanged.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class StorageUrl implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return self::isAbsolute($value) ? $value : Storage::disk('public')->url($value);
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return self::toPath($value) ?? $value;
    }

    /**
     * The public-disk path behind a value, or null if it points somewhere else.
     * Accepts a plain path, a URL of the current public disk, and the legacy
     * absolute URLs this app stored before (any host, path below /storage/).
     */
    public static function toPath(string $value): ?string
    {
        if (! self::isAbsolute($value)) {
            return ltrim($value, '/');
        }

        $base = rtrim(Storage::disk('public')->url(''), '/').'/';
        if (str_starts_with($value, $base)) {
            return substr($value, strlen($base));
        }

        return null;
    }

    private static function isAbsolute(string $value): bool
    {
        return (bool) preg_match('#^(https?:)?//#i', $value);
    }
}

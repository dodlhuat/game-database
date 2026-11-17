<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reads a mechanics/tags list from database/data: one name per line, blank
 * lines and lines starting with # are ignored. Rejects names that produce an
 * empty slug and duplicates (same slug), so the lists stay clean.
 */
final class TaxonomyList
{
    public static function path(string $name, ?string $dir = null): string
    {
        return ($dir ?? database_path('data'))."/{$name}.txt";
    }

    /**
     * @return list<string>
     *
     * @throws RuntimeException with every problem found, line by line
     */
    public static function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Liste fehlt: {$path}");
        }

        $names = [];
        $slugs = [];
        $problems = [];
        foreach (preg_split('/\R/', (string) file_get_contents($path)) ?: [] as $i => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $slug = Str::slug($line);
            if ($slug === '') {
                $problems[] = 'Zeile '.($i + 1).": „{$line}“ ergibt keinen gültigen Slug";
            } elseif (isset($slugs[$slug])) {
                $problems[] = 'Zeile '.($i + 1).": „{$line}“ ist doppelt (wie „{$slugs[$slug]}“)";
            } else {
                $slugs[$slug] = $line;
                $names[] = $line;
            }
        }

        if ($problems !== []) {
            throw new RuntimeException("Fehler in {$path}:\n  - ".implode("\n  - ", $problems));
        }

        if ($names === []) {
            throw new RuntimeException("Liste ist leer: {$path}");
        }

        return $names;
    }
}

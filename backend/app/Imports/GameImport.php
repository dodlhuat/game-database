<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GameImport implements ToModel, WithHeadingRow
{
    public int $newCount = 0;
    public int $updatedCount = 0;

    public function model(array $row): ?Game
    {
        $title = trim($row['title'] ?? '');
        if (empty($title)) {
            return null;
        }

        $slug = trim($row['slug'] ?? '');
        if (empty($slug)) {
            $slug = Str::slug($title);
        }

        // Kategorie per Name auflösen
        $categoryId = null;
        if (!empty($row['category'])) {
            $category = Category::firstOrCreate(
                ['name' => trim($row['category'])],
                ['slug' => Str::slug(trim($row['category']))]
            );
            $categoryId = $category->id;
        }

        // ID vorhanden → Update, sonst → Neu
        $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
        $existing = $id ? Game::find($id) : null;
        $isNew = $existing === null;

        $data = array_filter([
            'title'             => $title,
            'slug'              => $slug,
            'short_description' => isset($row['short_description']) && $row['short_description'] !== '' ? substr(trim($row['short_description']), 0, 500) : null,
            'description'       => isset($row['description']) && $row['description'] !== '' ? trim($row['description']) : null,
            'category_id'       => $categoryId,
            'min_players'       => isset($row['min_players']) && $row['min_players'] !== '' ? (int) $row['min_players'] : null,
            'max_players'       => isset($row['max_players']) && $row['max_players'] !== '' ? (int) $row['max_players'] : null,
            'min_age'           => isset($row['min_age']) && $row['min_age'] !== '' ? (int) $row['min_age'] : null,
            'duration_min'      => isset($row['duration_min']) && $row['duration_min'] !== '' ? (int) $row['duration_min'] : null,
            'duration_max'      => isset($row['duration_max']) && $row['duration_max'] !== '' ? (int) $row['duration_max'] : null,
            'difficulty'        => isset($row['difficulty']) && in_array(strtoupper($row['difficulty']), ['EASY', 'MEDIUM', 'HARD', 'EXPERT']) ? strtoupper($row['difficulty']) : null,
            'language'          => isset($row['language']) && $row['language'] !== '' ? trim($row['language']) : null,
            'year'              => isset($row['year']) && $row['year'] !== '' ? (int) $row['year'] : null,
            'is_active'         => isset($row['is_active']) && $row['is_active'] !== '' ? (bool)(int) $row['is_active'] : true,
        ], fn($v) => $v !== null);

        if ($isNew) {
            $game = Game::create($data);
            $this->newCount++;
        } else {
            $existing->update($data);
            $game = $existing;
            $this->updatedCount++;
        }

        // Tags synchronisieren (kommagetrennt)
        if (!empty($row['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $row['tags'])));
            $tagIds = [];
            foreach ($tagNames as $tagName) {
                $tag = Tag::firstOrCreate(
                    ['name' => $tagName],
                    ['slug' => Str::slug($tagName)]
                );
                $tagIds[] = $tag->id;
            }
            $game->tags()->sync($tagIds);
        }

        return null; // Model selbst gespeichert
    }
}

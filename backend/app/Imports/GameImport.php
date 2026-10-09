<?php

namespace App\Imports;

use App\Models\Game;
use App\Models\Language;
use App\Models\Mechanic;
use App\Models\Tag;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GameImport implements ToModel, WithHeadingRow
{
    public int $newCount = 0;

    public int $updatedCount = 0;

    /** @param array<string, mixed> $row */
    public function model(array $row): ?Game
    {
        $title = trim($this->text($row, 'title') ?? '');
        if (empty($title)) {
            return null;
        }

        $slug = trim($this->text($row, 'slug') ?? '');
        if (empty($slug)) {
            $slug = Str::slug($title);
        }

        // ID vorhanden → Update, sonst → Neu
        $id = $this->number($row, 'id');
        $existing = $id ? Game::find($id) : null;
        $isNew = $existing === null;

        $difficultyText = $this->text($row, 'difficulty');
        $difficulty = $difficultyText !== null ? strtoupper($difficultyText) : null;

        $data = array_filter([
            'title' => $title,
            'slug' => $slug,
            'short_description' => ($shortDescription = $this->text($row, 'short_description')) !== null ? substr(trim($shortDescription), 0, 500) : null,
            'description' => ($description = $this->text($row, 'description')) !== null ? trim($description) : null,
            'min_players' => $this->number($row, 'min_players'),
            'max_players' => $this->number($row, 'max_players'),
            'min_age' => $this->number($row, 'min_age'),
            'duration_min' => $this->number($row, 'duration_min'),
            'duration_max' => $this->number($row, 'duration_max'),
            'difficulty' => $difficulty !== null && in_array($difficulty, ['EASY', 'MEDIUM', 'HARD', 'EXPERT'], true) ? $difficulty : null,
            'year' => $this->number($row, 'year'),
            'is_active' => ($isActive = $this->number($row, 'is_active')) !== null ? (bool) $isActive : true,
        ], fn ($v) => $v !== null);

        if ($isNew) {
            $game = Game::create($data);
            $this->newCount++;
        } else {
            $existing->update($data);
            $game = $existing;
            $this->updatedCount++;
        }

        // Sprachen synchronisieren (kommagetrennt)
        if (($languages = $this->text($row, 'language')) !== null) {
            $langNames = $this->names($languages);
            $langIds = [];
            foreach ($langNames as $langName) {
                $lang = Language::firstOrCreate(['name' => $langName]);
                $langIds[] = $lang->id;
            }
            $game->languages()->sync($langIds);
        }

        // Tags synchronisieren (kommagetrennt)
        if (($tags = $this->text($row, 'tags')) !== null) {
            $tagNames = $this->names($tags);
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

        // Mechaniken synchronisieren (kommagetrennt)
        if (($mechanics = $this->text($row, 'mechanics')) !== null) {
            $mechanicNames = $this->names($mechanics);
            $mechanicIds = [];
            foreach ($mechanicNames as $mechanicName) {
                $mechanic = Mechanic::firstOrCreate(
                    ['name' => $mechanicName],
                    ['slug' => Str::slug($mechanicName)]
                );
                $mechanicIds[] = $mechanic->id;
            }
            $game->mechanics()->sync($mechanicIds);
        }

        return null; // Model selbst gespeichert
    }

    /**
     * Cell as string, or null when the column is missing or empty.
     *
     * @param  array<string, mixed>  $row
     */
    private function text(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /** @param  array<string, mixed>  $row */
    private function number(array $row, string $key): ?int
    {
        $value = $this->text($row, $key);

        return $value === null ? null : (int) $value;
    }

    /** @return list<string> */
    private function names(string $commaSeparated): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $commaSeparated))));
    }
}

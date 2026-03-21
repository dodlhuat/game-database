<?php

namespace App\Exports;

use App\Models\Game;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class GameExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection(): Collection
    {
        return Game::with(['category', 'tags'])->orderBy('title')->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'title',
            'slug',
            'short_description',
            'description',
            'category',
            'min_players',
            'max_players',
            'min_age',
            'duration_min',
            'duration_max',
            'difficulty',
            'language',
            'year',
            'is_active',
            'tags',
        ];
    }

    public function map($game): array
    {
        return [
            $game->id,
            $game->title,
            $game->slug,
            $game->short_description,
            $game->description,
            $game->category?->name,
            $game->min_players,
            $game->max_players,
            $game->min_age,
            $game->duration_min,
            $game->duration_max,
            $game->difficulty,
            $game->language,
            $game->year,
            $game->is_active ? 1 : 0,
            $game->tags->pluck('name')->join(', '),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

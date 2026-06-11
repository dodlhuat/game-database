<?php

namespace App\Exports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** @implements WithMapping<\App\Models\Category> */
class CategoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /** @return Collection<int, \App\Models\Category> */
    public function collection(): Collection
    {
        // Flat list: parents first, then children, ordered by sort_order / name
        $parents = Category::withCount('games')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rows = collect();
        foreach ($parents as $parent) {
            $rows->push($parent);
            $children = Category::withCount('games')
                ->where('parent_id', $parent->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            foreach ($children as $child) {
                $rows->push($child);
            }
        }

        return $rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'id',
            'name',
            'slug',
            'parent_id',
            'icon_url',
            'sort_order',
            'is_active',
        ];
    }

    /** @return array<int, mixed> */
    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->slug,
            $category->parent_id,
            $category->icon_url,
            $category->sort_order,
            $category->is_active ? 1 : 0,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

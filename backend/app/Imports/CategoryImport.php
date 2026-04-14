<?php

namespace App\Imports;

use App\Models\Category;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements ToModel, WithHeadingRow
{
    public int $newCount = 0;
    public int $updatedCount = 0;

    public function model(array $row): ?Category
    {
        $name = trim($row['name'] ?? '');
        if (empty($name)) {
            return null;
        }

        $slug = trim($row['slug'] ?? '');
        if (empty($slug)) {
            $slug = Str::slug($name);
        }

        $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
        $existing = $id ? Category::find($id) : null;
        $isNew = $existing === null;

        // Duplikat-Prüfung: kein Update (kein id) aber Name/Slug existiert bereits → ignorieren
        if ($isNew) {
            $duplicate = Category::where('slug', $slug)->orWhere('name', $name)->first();
            if ($duplicate) {
                return null;
            }
        }

        $parentId = null;
        if (isset($row['parent_id']) && $row['parent_id'] !== '' && $row['parent_id'] !== null) {
            $parentIdValue = (int) $row['parent_id'];
            if ($parentIdValue > 0 && Category::find($parentIdValue)) {
                $parentId = $parentIdValue;
            }
        }

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'parent_id'  => $parentId,
            'icon_url'   => isset($row['icon_url']) && $row['icon_url'] !== '' ? trim($row['icon_url']) : null,
            'sort_order' => isset($row['sort_order']) && $row['sort_order'] !== '' ? (int) $row['sort_order'] : 0,
            'is_active'  => isset($row['is_active']) && $row['is_active'] !== '' ? (bool)(int) $row['is_active'] : true,
        ];

        if ($isNew) {
            Category::create($data);
            $this->newCount++;
        } else {
            $existing->update($data);
            $this->updatedCount++;
        }

        return null; // Model selbst gespeichert
    }
}

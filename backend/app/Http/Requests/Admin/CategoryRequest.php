<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $route = $this->route('category');
        $categoryId = $route instanceof Category ? $route->id : null;

        $isPatch = $this->isMethod('PATCH');

        return [
            'name' => [$isPatch ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [$isPatch ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'icon_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ];
    }
}

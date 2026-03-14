<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gameId = $this->route('game')?->id;

        return [
            'title'       => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', Rule::unique('games', 'slug')->ignore($gameId)],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_players' => ['nullable', 'integer', 'min:1'],
            'max_players' => ['nullable', 'integer', 'min:1', 'gte:min_players'],
            'duration_min'=> ['nullable', 'integer', 'min:1'],
            'difficulty'  => ['nullable', 'in:EASY,MEDIUM,HARD,EXPERT'],
            'language'    => ['nullable', 'string', 'max:100'],
            'year'        => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'is_active'   => ['boolean'],
            'cover_image' => ['nullable', 'image', 'max:5120'], // max 5MB
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['integer', 'exists:tags,id'],
        ];
    }
}

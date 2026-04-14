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
            'title'             => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', Rule::unique('games', 'slug')->ignore($gameId)],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'category_id'       => ['nullable', 'integer', 'exists:categories,id'],
            'min_players'       => ['nullable', 'integer', 'min:1'],
            'max_players'       => ['nullable', 'integer', 'min:1', 'gte:min_players'],
            'min_age'           => ['nullable', 'integer', 'min:0', 'max:99'],
            'duration_min'      => ['nullable', 'integer', 'min:1'],
            'duration_max'      => ['nullable', 'integer', 'min:1', 'gte:duration_min'],
            'difficulty'        => ['nullable', 'in:EASY,MEDIUM,HARD,EXPERT'],
            'language'          => ['nullable', 'string', 'max:100'],
            'year'              => ['nullable', 'integer', 'min:1900', 'max:2100'],\n            'instagram_url'     => ['nullable', 'url', 'max:255'],
            'is_active'         => ['boolean'],
            'cover_image'       => ['nullable', 'image', 'max:5120'],
            'tag_ids'           => ['nullable', 'array'],
            'tag_ids.*'         => ['integer', 'exists:tags,id'],
        ];
    }
}

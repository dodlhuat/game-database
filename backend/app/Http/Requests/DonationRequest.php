<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'games' => ['required', 'array', 'min:1', 'max:20'],
            'games.*' => ['required', 'string', 'max:120'],
            'confirmed_complete' => ['required', 'accepted'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'max:5120'],
            'website' => ['prohibited'],
            'form_loaded_at' => ['required', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $loadedAt = (int) $this->input('form_loaded_at');
            $elapsed = (int) (microtime(true) * 1000) - $loadedAt;

            if ($elapsed < 3000) {
                $validator->errors()->add('form', 'Bitte versuche es erneut.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'games.required' => 'Bitte gib mindestens ein Spiel an.',
            'games.min' => 'Bitte gib mindestens ein Spiel an.',
            'games.*.required' => 'Bitte gib einen Spieletitel an.',
            'confirmed_complete.required' => 'Bitte bestätige, dass die Spiele vollständig sind.',
            'confirmed_complete.accepted' => 'Bitte bestätige, dass die Spiele vollständig sind.',
            'images.max' => 'Du kannst maximal 3 Bilder hochladen.',
            'images.*.image' => 'Bitte lade nur Bilddateien hoch.',
            'images.*.max' => 'Jedes Bild darf maximal 5 MB groß sein.',
        ];
    }
}

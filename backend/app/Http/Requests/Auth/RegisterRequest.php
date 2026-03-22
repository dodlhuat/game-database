<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'newsletter_opt_in' => ['boolean'],
            'terms_accepted' => ['required', 'accepted'],
            'website'        => ['prohibited'],
            'form_loaded_at' => ['required', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $loadedAt = (int) $this->input('form_loaded_at');
            $elapsed = (int) (microtime(true) * 1000) - $loadedAt;

            if ($elapsed < 3000) {
                $validator->errors()->add('form', 'Bitte versuche es erneut.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Name ist erforderlich.',
            'email.required'         => 'E-Mail ist erforderlich.',
            'email.unique'           => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.required'      => 'Passwort ist erforderlich.',
            'password.min'           => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'password.confirmed'     => 'Die Passwörter stimmen nicht überein.',
            'terms_accepted.required' => 'Du musst die Nutzungsbedingungen akzeptieren.',
            'terms_accepted.accepted' => 'Du musst die Nutzungsbedingungen akzeptieren.',
        ];
    }
}

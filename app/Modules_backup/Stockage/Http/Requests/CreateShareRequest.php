<?php

namespace App\Modules\Stockage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShareRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'password' => 'nullable|string|min:6',
            'allow_download' => 'nullable|boolean',
            'allow_preview' => 'nullable|boolean',
            'max_downloads' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'max_downloads.min' => 'Le nombre maximum de téléchargements doit être au moins 1.',
            'expires_at.after' => 'La date d\'expiration doit être dans le futur.',
        ];
    }
}

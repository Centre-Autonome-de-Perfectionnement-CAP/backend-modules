<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenamePieceRequest extends FormRequest
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
            'piece_key' => 'required|string',
            'custom_name' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'piece_key.required' => 'La clé de la pièce est requise.',
            'piece_key.string' => 'La clé de la pièce doit être une chaîne de caractères.',
            'custom_name.required' => 'Le nouveau nom est requis.',
            'custom_name.string' => 'Le nouveau nom doit être une chaîne de caractères.',
            'custom_name.max' => 'Le nouveau nom ne peut pas dépasser 255 caractères.',
        ];
    }
}

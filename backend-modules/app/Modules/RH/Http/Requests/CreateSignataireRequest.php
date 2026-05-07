<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSignataireRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nom' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom est requis',
            'nom.string' => 'Le nom doit être une chaîne de caractères',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères',
            'role_id.required' => 'Le rôle est requis',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas',
        ];
    }
}

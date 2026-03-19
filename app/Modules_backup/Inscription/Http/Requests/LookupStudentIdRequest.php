<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupStudentIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => 'required|string',
            'first_names' => 'required|string',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est requis',
            'first_names.required' => 'Les prénoms sont requis',
            'birth_date.required' => 'La date de naissance est requise',
            'birth_date.date' => 'La date de naissance doit être une date valide',
            'birth_place.required' => 'Le lieu de naissance est requis',
        ];
    }
}

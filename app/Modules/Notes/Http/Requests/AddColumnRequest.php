<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',
            'notes' => 'required|array',
            'notes.*' => 'required|numeric|min:-1|max:20',
            'is_retake' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => 'L\'ID du programme est requis',
            'program_id.exists' => 'Le programme spécifié n\'existe pas',
            'notes.required' => 'Les notes sont requises',
            'notes.array' => 'Les notes doivent être un tableau',
            'notes.*.numeric' => 'Chaque note doit être numérique',
            'notes.*.min' => 'Chaque note doit être supérieure ou égale à -1',
            'notes.*.max' => 'Chaque note doit être inférieure ou égale à 20',
        ];
    }
}
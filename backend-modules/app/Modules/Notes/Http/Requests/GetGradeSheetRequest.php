<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetGradeSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',
            'cohort' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => 'L\'ID du programme est requis',
            'program_id.exists' => 'Le programme spécifié n\'existe pas',
            'cohort.string' => 'La cohorte doit être une chaîne de caractères',
        ];
    }
}
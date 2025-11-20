<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSingleGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_pending_student_id' => 'required|exists:student_pending_students,id',
            'program_id' => 'required|exists:programs,id',
            'position' => 'required|integer|min:0',
            'value' => 'required|numeric|min:-1|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'student_pending_student_id.required' => 'L\'ID de l\'étudiant est requis',
            'student_pending_student_id.exists' => 'L\'étudiant spécifié n\'existe pas',
            'program_id.required' => 'L\'ID du programme est requis',
            'program_id.exists' => 'Le programme spécifié n\'existe pas',
            'position.required' => 'La position est requise',
            'position.integer' => 'La position doit être un entier',
            'position.min' => 'La position doit être supérieure ou égale à 0',
            'value.required' => 'La valeur est requise',
            'value.numeric' => 'La valeur doit être numérique',
            'value.min' => 'La valeur doit être supérieure ou égale à -1',
            'value.max' => 'La valeur doit être inférieure ou égale à 20',
        ];
    }
}
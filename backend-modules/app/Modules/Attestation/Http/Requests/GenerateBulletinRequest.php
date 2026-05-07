<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_pending_student_id' => 'required|exists:student_pending_student,id',
            'academic_year_id' => 'required|exists:academic_years,id',

        ];
    }

    public function messages(): array
    {
        return [
            'student_pending_student_id.required' => 'L\'identifiant de l\'étudiant est requis',
            'student_pending_student_id.exists' => 'L\'étudiant n\'existe pas',
            'academic_year_id.required' => 'L\'année académique est requise',
            'academic_year_id.exists' => 'L\'année académique n\'existe pas',
            'semester.required' => 'Le semestre est requis',
            'semester.integer' => 'Le semestre doit être un nombre',
            'semester.min' => 'Le semestre doit être 1 ou 2',
            'semester.max' => 'Le semestre doit être 1 ou 2',
        ];
    }
}

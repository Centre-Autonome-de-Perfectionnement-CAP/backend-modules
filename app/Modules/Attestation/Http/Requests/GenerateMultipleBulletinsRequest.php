<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMultipleBulletinsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulletins' => 'required|array',
            'bulletins.*.student_pending_student_id' => 'required|integer|exists:student_pending_student,id',
            'bulletins.*.academic_year_id' => 'required|integer|exists:academic_years,id',
        ];
    }

    public function messages(): array
    {
        return [
            'bulletins.required' => 'La liste des bulletins est requise',
            'bulletins.array' => 'La liste des bulletins doit être un tableau',
            'bulletins.*.student_pending_student_id.required' => 'L\'identifiant de l\'étudiant est requis',
            'bulletins.*.student_pending_student_id.integer' => 'L\'identifiant de l\'étudiant doit être un nombre',
            'bulletins.*.student_pending_student_id.exists' => 'L\'étudiant n\'existe pas',
            'bulletins.*.academic_year_id.required' => 'L\'année académique est requise',
            'bulletins.*.academic_year_id.integer' => 'L\'année académique doit être un nombre',
            'bulletins.*.academic_year_id.exists' => 'L\'année académique n\'existe pas',
        ];
    }
}
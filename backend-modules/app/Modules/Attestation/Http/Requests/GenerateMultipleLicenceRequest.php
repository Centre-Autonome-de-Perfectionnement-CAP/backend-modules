<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMultipleLicenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_pending_student_ids' => 'required|array',
            'student_pending_student_ids.*' => 'required|integer|exists:student_pending_student,id'
        ];
    }

    public function messages(): array
    {
        return [
            'student_pending_student_ids.required' => 'La liste des étudiants est requise',
            'student_pending_student_ids.array' => 'La liste des étudiants doit être un tableau',
            'student_pending_student_ids.*.required' => 'L\'identifiant de l\'étudiant est requis',
            'student_pending_student_ids.*.integer' => 'L\'identifiant de l\'étudiant doit être un nombre',
            'student_pending_student_ids.*.exists' => 'L\'étudiant n\'existe pas',
        ];
    }
}
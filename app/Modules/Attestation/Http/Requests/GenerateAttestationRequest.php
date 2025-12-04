<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAttestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_pending_student_id' => 'required|exists:student_pending_student,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_pending_student_id.required' => 'L\'identifiant de l\'étudiant est requis',
            'student_pending_student_id.exists' => 'L\'étudiant n\'existe pas',
        ];
    }
}

<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMultipleDefinitiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_pending_student_ids'   => 'required|array|min:1',
            'student_pending_student_ids.*' => 'required|integer|exists:student_pending_student,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_pending_student_ids.required' => 'La liste des étudiants est obligatoire.',
            'student_pending_student_ids.array'    => 'La liste des étudiants doit être un tableau.',
            'student_pending_student_ids.min'      => 'Au moins un étudiant doit être sélectionné.',
            'student_pending_student_ids.*.exists' => 'Un ou plusieurs étudiants sélectionnés sont introuvables.',
        ];
    }
}

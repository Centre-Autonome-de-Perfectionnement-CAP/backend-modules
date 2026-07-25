<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMultipleInscriptionRequest extends FormRequest
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
}

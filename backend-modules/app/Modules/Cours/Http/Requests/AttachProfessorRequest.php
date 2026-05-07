<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'professor_id' => 'required|integer|exists:professors,id',
        ];
    }

    public function messages(): array
    {
        return [
            'professor_id.required' => 'Le professeur est requis',
            'professor_id.integer' => 'L\'identifiant du professeur doit être un entier',
            'professor_id.exists' => 'Le professeur spécifié n\'existe pas',
        ];
    }
}

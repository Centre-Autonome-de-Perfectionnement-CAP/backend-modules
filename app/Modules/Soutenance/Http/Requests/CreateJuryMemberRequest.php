<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateJuryMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'professor_id' => 'nullable|exists:professors,id',
            'grade_id' => 'nullable|exists:grades,id',
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:president,directeur,rapporteur,examinateur',
        ];
    }

    public function messages(): array
    {
        return [
            'professor_id.exists' => 'Le professeur sélectionné n\'existe pas',
            'grade_id.exists' => 'Le grade sélectionné n\'existe pas',
            'name.required' => 'Le nom est requis',
            'role.required' => 'Le rôle est requis',
            'role.in' => 'Le rôle doit être: president, rapporteur, examinateur ou directeur',
        ];
    }
}

<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseElementProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_element_id' => 'sometimes|integer|exists:course_elements,id',
            'professor_id' => 'sometimes|integer|exists:professors,id',
            'is_primary' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'course_element_id.integer' => 'L\'identifiant de l\'élément de cours doit être un entier',
            'course_element_id.exists' => 'L\'élément de cours spécifié n\'existe pas',
            'professor_id.integer' => 'L\'identifiant du professeur doit être un entier',
            'professor_id.exists' => 'Le professeur spécifié n\'existe pas',
            'is_primary.boolean' => 'Le champ principal doit être vrai ou faux',
        ];
    }
}

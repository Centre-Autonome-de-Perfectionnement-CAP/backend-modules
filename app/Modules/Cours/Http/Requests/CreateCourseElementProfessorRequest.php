<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseElementProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_element_id' => 'required|integer|exists:course_elements,id',
            'professor_id' => 'required|integer|exists:professors,id',
            'principal_professor_id' => 'required|integer|exists:professors,id',
            'is_primary' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'course_element_id.required' => 'L\'élément de cours est requis',
            'course_element_id.exists' => 'L\'élément de cours spécifié n\'existe pas',
            'professor_id.required' => 'Le professeur est requis',
            'professor_id.exists' => 'Le professeur spécifié n\'existe pas',
            'principal_professor_id.required' => 'Le professeur principal est requis',
            'principal_professor_id.exists' => 'Le professeur principal spécifié n\'existe pas',
            'is_primary.boolean' => 'Le champ principal doit être vrai ou faux',
        ];
    }
}

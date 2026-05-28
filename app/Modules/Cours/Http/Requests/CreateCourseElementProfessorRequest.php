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
            'professor_id'      => 'required|integer|exists:professors,id',
            'is_primary'        => 'nullable|boolean',
            'class_group_id'    => 'nullable|integer|exists:class_groups,id',   // ← ajouté
            'academic_year_id'  => 'nullable|integer|exists:academic_years,id', // ← ajouté
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_primary' => $this->is_primary ?? false,
        ]);
    }

    public function messages(): array
    {
        return [
            'course_element_id.required' => "L'élément de cours est requis",
            'course_element_id.exists'   => "L'élément de cours spécifié n'existe pas",
            'professor_id.required'      => 'Le professeur est requis',
            'professor_id.exists'        => 'Le professeur spécifié n\'existe pas',
            'is_primary.boolean'         => 'Le champ principal doit être vrai ou faux',
            'class_group_id.exists'      => 'La classe spécifiée n\'existe pas',     // ← ajouté
            'academic_year_id.exists'    => "L'année académique spécifiée n'existe pas", // ← ajouté
        ];
    }
}

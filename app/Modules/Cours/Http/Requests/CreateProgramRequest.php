<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_group_id' => 'required|exists:class_groups,id',
            'course_element_professor_id' => 'required|exists:course_element_professor,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'weighting' => 'nullable|array',
            'weighting.*' => 'nullable|numeric|min:0|max:100',
            'retake_weighting' => 'nullable|array',
            'retake_weighting.*' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'class_group_id.required' => 'Le groupe de classe est obligatoire.',
            'class_group_id.exists' => 'Le groupe de classe sélectionné n\'existe pas.',
            'course_element_professor_id.required' => 'L\'assignation cours-professeur est obligatoire.',
            'course_element_professor_id.exists' => 'L\'assignation cours-professeur sélectionnée n\'existe pas.',
            'academic_year_id.required' => 'L\'année académique est obligatoire.',
            'academic_year_id.exists' => 'L\'année académique sélectionnée n\'existe pas.',
            'weighting.array' => 'La pondération doit être un tableau.',
            'weighting.*.numeric' => 'Chaque pondération doit être un nombre.',
            'weighting.*.min' => 'Chaque pondération doit être supérieure ou égale à 0.',
            'weighting.*.max' => 'Chaque pondération doit être inférieure ou égale à 100.',
        ];
    }

    /**
     * Exemple de structure pour la pondération:
     * {
     *     "CC": 30,
     *     "TP": 20,
     *     "EXAMEN": 50
     * }
     * Total doit faire 100
     */
    public function attributes(): array
    {
        return [
            'class_group_id' => 'groupe de classe',
            'course_element_professor_id' => 'assignation cours-professeur',
            'weighting' => 'pondération',
        ];
    }
}

<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateProgramsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'programs' => 'required|array|min:1',
            'programs.*.class_group_id' => 'required|exists:class_groups,id',
            'programs.*.course_element_professor_id' => [
                'required',
                'exists:course_element_professor,id',
            ],
            'programs.*.weighting' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if (!is_array($value)) {
                        return;
                    }
                    
                    // Vérifier que toutes les valeurs sont numériques
                    foreach ($value as $key => $weight) {
                        if (!is_numeric($weight)) {
                            $fail("La pondération '{$key}' dans {$attribute} doit être un nombre.");
                            return;
                        }
                    }
                    
                    // Vérifier que la somme fait 100
                    $sum = array_sum($value);
                    if ($sum != 100) {
                        $fail("La somme des pondérations dans {$attribute} doit être égale à 100 (actuellement: {$sum}).");
                    }
                },
            ],
            'programs.*.weighting.*' => 'numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'programs.required' => 'Au moins un programme est requis.',
            'programs.array' => 'Les programmes doivent être un tableau.',
            'programs.*.class_group_id.required' => 'Le groupe de classe est obligatoire pour chaque programme.',
            'programs.*.class_group_id.exists' => 'Le groupe de classe sélectionné n\'existe pas.',
            'programs.*.course_element_professor_id.required' => 'L\'assignation cours-professeur est obligatoire.',
            'programs.*.course_element_professor_id.exists' => 'L\'assignation cours-professeur sélectionnée n\'existe pas.',
            'programs.*.weighting.array' => 'La pondération doit être un tableau.',
            'programs.*.weighting.*.numeric' => 'Chaque pondération doit être un nombre.',
            'programs.*.weighting.*.min' => 'Chaque pondération doit être supérieure ou égale à 0.',
            'programs.*.weighting.*.max' => 'Chaque pondération doit être inférieure ou égale à 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'programs.*.class_group_id' => 'groupe de classe',
            'programs.*.course_element_professor_id' => 'assignation cours-professeur',
            'programs.*.weighting' => 'pondération',
        ];
    }
}

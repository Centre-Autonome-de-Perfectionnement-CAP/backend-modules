<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programId = $this->route('program')->id;
        
        return [
            'class_group_id' => 'sometimes|exists:class_groups,id',
            'course_element_professor_id' => [
                'sometimes',
                'exists:course_element_professor,id',
                // Vérifier que cette combinaison n'existe pas déjà (sauf pour ce programme)
                function ($attribute, $value, $fail) use ($programId) {
                    $exists = \App\Modules\Cours\Models\Program::where('class_group_id', $this->class_group_id ?? $this->route('program')->class_group_id)
                        ->where('course_element_professor_id', $value)
                        ->where('id', '!=', $programId)
                        ->exists();
                    
                    if ($exists) {
                        $fail('Ce cours est déjà assigné à ce groupe de classe.');
                    }
                },
            ],
            'weighting' => [
                'sometimes',
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if (!is_array($value)) {
                        return;
                    }
                    
                    // Vérifier que toutes les valeurs sont numériques
                    foreach ($value as $key => $weight) {
                        if (!is_numeric($weight)) {
                            $fail("La pondération '{$key}' doit être un nombre.");
                            return;
                        }
                    }
                    
                    // Vérifier que la somme fait 100
                    $sum = array_sum($value);
                    if ($sum != 100) {
                        $fail("La somme des pondérations doit être égale à 100 (actuellement: {$sum}).");
                    }
                },
            ],
            'weighting.*' => 'numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'class_group_id.exists' => 'Le groupe de classe sélectionné n\'existe pas.',
            'course_element_professor_id.exists' => 'L\'assignation cours-professeur sélectionnée n\'existe pas.',
            'weighting.array' => 'La pondération doit être un tableau.',
            'weighting.*.numeric' => 'Chaque pondération doit être un nombre.',
            'weighting.*.min' => 'Chaque pondération doit être supérieure ou égale à 0.',
            'weighting.*.max' => 'Chaque pondération doit être inférieure ou égale à 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'class_group_id' => 'groupe de classe',
            'course_element_professor_id' => 'assignation cours-professeur',
            'weighting' => 'pondération',
        ];
    }
}

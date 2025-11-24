<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetWeightingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required',
            'weighting' => 'required|array',
            'weighting.*' => 'required|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => 'L\'ID du programme est requis',
            'program_id.exists' => 'Le programme spécifié n\'existe pas',
            'weighting.required' => 'La pondération est requise',
            'weighting.array' => 'La pondération doit être un tableau',
            'weighting.*.integer' => 'Chaque pondération doit être un entier',
            'weighting.*.min' => 'Chaque pondération doit être supérieure ou égale à 0',
            'weighting.*.max' => 'Chaque pondération doit être inférieure ou égale à 100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (array_sum($this->weighting ?? []) !== 100) {
                $validator->errors()->add('weighting', 'La somme de la pondération doit faire 100%');
            }
        });
    }
}
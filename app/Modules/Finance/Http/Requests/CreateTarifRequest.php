<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTarifRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
<<<<<<< HEAD
<<<<<<< HEAD
            'type' => 'required|string|in:inscription,formation,penalty',
            'libelle' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'academic_year_id' => 'required|exists:academic_years,id',
=======
            'type' => 'required|string|in:inscription,formation,sponsorise,penalty,exoneration',
            'libelle' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'cycle_id' => 'nullable|exists:cycles,id',
            'department_id' => 'nullable|exists:departments,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
>>>>>>> eea2b06 (draft)
=======
            'type' => 'required|string|in:inscription,formation,penalty',
            'libelle' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'academic_year_id' => 'required|exists:academic_years,id',
>>>>>>> 7854261 (commit)
            'is_active' => 'boolean',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_type' => 'nullable|string|in:fixed,percentage',
            'penalty_active' => 'boolean',
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 7854261 (commit)
            'class_groups' => 'required|array|min:1',
            'class_groups.*.academic_year_id' => 'required|exists:academic_years,id',
            'class_groups.*.department_id' => 'required|exists:departments,id',
            'class_groups.*.study_level' => 'required|integer|min:1|max:5',
<<<<<<< HEAD
            'uemoa_training_fee' => 'nullable|numeric|min:0',
            'non_uemoa_training_fee' => 'nullable|numeric|min:0',
=======
            'exoneration_type' => 'nullable|string|in:fixed,percentage',
            'exoneration_value' => 'nullable|numeric|min:0',
>>>>>>> eea2b06 (draft)
=======
>>>>>>> 7854261 (commit)
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Le type de tarif est obligatoire',
<<<<<<< HEAD
<<<<<<< HEAD
            'type.in' => 'Le type de tarif doit être: inscription, formation ou penalty',
            'class_groups.required' => 'Vous devez sélectionner au moins une classe',
=======
            'type.in' => 'Le type de tarif doit être: inscription, formation, sponsorise, penalty ou exoneration',
>>>>>>> eea2b06 (draft)
=======
            'type.in' => 'Le type de tarif doit être: inscription, formation ou penalty',
            'class_groups.required' => 'Vous devez sélectionner au moins une classe',
>>>>>>> 7854261 (commit)
            'libelle.required' => 'Le libellé est obligatoire',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être positif',
        ];
    }
}
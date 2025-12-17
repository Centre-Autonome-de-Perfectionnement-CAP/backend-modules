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
            'type' => 'required|string|in:inscription,formation,penalty',
            'libelle' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_active' => 'boolean',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_type' => 'nullable|string|in:fixed,percentage',
            'penalty_active' => 'boolean',
            'class_groups' => 'required|array|min:1',
            'class_groups.*.academic_year_id' => 'required|exists:academic_years,id',
            'class_groups.*.department_id' => 'required|exists:departments,id',
            'class_groups.*.study_level' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Le type de tarif est obligatoire',
            'type.in' => 'Le type de tarif doit être: inscription, formation ou penalty',
            'class_groups.required' => 'Vous devez sélectionner au moins une classe',
            'libelle.required' => 'Le libellé est obligatoire',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être positif',
        ];
    }
}
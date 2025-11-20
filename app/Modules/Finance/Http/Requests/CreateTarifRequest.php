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
            'type' => 'required|string|in:inscription,formation,sponsorise,penalty,exoneration',
            'libelle' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'cycle_id' => 'nullable|exists:cycles,id',
            'department_id' => 'nullable|exists:departments,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'is_active' => 'boolean',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_type' => 'nullable|string|in:fixed,percentage',
            'penalty_active' => 'boolean',
            'exoneration_type' => 'nullable|string|in:fixed,percentage',
            'exoneration_value' => 'nullable|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Le type de tarif est obligatoire',
            'type.in' => 'Le type de tarif doit être: inscription, formation, sponsorise, penalty ou exoneration',
            'libelle.required' => 'Le libellé est obligatoire',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être positif',
        ];
    }
}
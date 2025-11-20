<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTarifRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'sometimes|string|in:inscription,formation,sponsorise,penalty,exoneration',
            'libelle' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
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
}
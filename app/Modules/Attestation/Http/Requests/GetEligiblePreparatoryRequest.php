<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetEligiblePreparatoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'department_id' => 'nullable|exists:departments,id',
            'cohort' => 'nullable|string',
            'search' => 'nullable|string',
        ];
    }
}

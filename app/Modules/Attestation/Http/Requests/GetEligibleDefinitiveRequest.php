<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetEligibleDefinitiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'department_id'    => 'nullable|integer|exists:departments,id',
            'cohort'           => 'nullable|string|max:20',
            'search'           => 'nullable|string|max:100',
        ];
    }
}

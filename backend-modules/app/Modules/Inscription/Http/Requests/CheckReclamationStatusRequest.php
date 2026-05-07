<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckReclamationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'L\'année académique est requise',
            'academic_year_id.integer' => 'L\'année académique doit être un entier',
            'academic_year_id.exists' => 'L\'année académique spécifiée n\'existe pas',
        ];
    }
}

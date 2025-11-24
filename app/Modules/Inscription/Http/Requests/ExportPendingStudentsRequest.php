<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportPendingStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohort' => 'required|not_in:all',
            'year' => 'nullable|string',
            'filiere' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
            'cohort.not_in' => 'Veuillez sélectionner une cohorte spécifique',
        ];
    }
}

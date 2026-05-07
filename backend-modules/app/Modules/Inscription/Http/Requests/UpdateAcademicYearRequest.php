<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year_start' => ['sometimes', 'date'],
            'year_end' => ['sometimes', 'date', 'after:year_start'],
            'submission_start' => ['sometimes', 'date'],
            'submission_end' => ['sometimes', 'date', 'after:submission_start'],
            'departments' => ['sometimes', 'array', 'min:1'],
            'departments.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'year_end.after' => 'La date de fin doit être après la date de début',
            'submission_end.after' => 'La date de fin de soumission doit être après la date de début de soumission',
            'departments.array' => 'Les départements doivent être un tableau',
            'departments.min' => 'Au moins un département est requis',
            'departments.*.integer' => 'Chaque département doit être un ID valide',
            'departments.*.exists' => 'Le département spécifié n\'existe pas',
        ];
    }
}

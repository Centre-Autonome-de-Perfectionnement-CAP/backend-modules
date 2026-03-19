<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendPeriodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYear = $this->route('academicYear');
        
        return [
            'start_date' => ['required', 'date'],
            'old_end_date' => ['required', 'date', 'after:start_date'],
            'new_end_date' => ['required', 'date', 'after:old_end_date', 'before_or_equal:' . $academicYear->year_end],
            'departments' => ['required', 'array', 'min:1'],
            'departments.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'La date de début est requise',
            'old_end_date.required' => 'L\'ancienne date de fin est requise',
            'old_end_date.after' => 'L\'ancienne date de fin doit être après la date de début',
            'new_end_date.required' => 'La nouvelle date de fin est requise',
            'new_end_date.after' => 'La nouvelle date de fin doit être après l\'ancienne',
            'new_end_date.before_or_equal' => 'La nouvelle date de fin doit être dans la période de l\'année académique',
            'departments.required' => 'Au moins un département est requis',
            'departments.array' => 'Les départements doivent être un tableau',
            'departments.min' => 'Au moins un département est requis',
            'departments.*.integer' => 'Chaque département doit être un ID valide',
            'departments.*.exists' => 'Le département spécifié n\'existe pas',
        ];
    }
}

<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagePeriodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYear = $this->route('academicYear');
        
        $rules = [
            'type' => ['required', 'string', 'in:depot,reclamation'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'departments' => ['required', 'array', 'min:1'],
            'departments.*' => ['integer', 'exists:departments,id'],
        ];



        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de période est requis',
            'type.in' => 'Le type de période doit être "depot" ou "reclamation"',
            'start_date.required' => 'La date de début est requise',
            'end_date.required' => 'La date de fin est requise',
            'end_date.after' => 'La date de fin doit être après la date de début',
            'departments.required' => 'Au moins un département est requis',
            'departments.array' => 'Les départements doivent être un tableau',
            'departments.min' => 'Au moins un département est requis',
            'departments.*.integer' => 'Chaque département doit être un ID valide',
            'departments.*.exists' => 'Le département spécifié n\'existe pas',
        ];
    }
}

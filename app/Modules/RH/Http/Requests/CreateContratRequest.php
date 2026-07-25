<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contrat_number' => 'nullable',
            'division'         => 'nullable|string|max:100',
            'professor_id' => 'required|exists:professors,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'start_date'       => 'required|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'amount'           => 'required|numeric|min:0',
           'status' => 'nullable|in:pending,signed,ongoing,completed,cancelled',
            'notes'            => 'nullable|string',
        ];
    }
}

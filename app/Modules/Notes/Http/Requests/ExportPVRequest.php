<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportPVRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|string',
            'cohort' => 'nullable|string',
            'validation_average' => 'nullable|numeric|min:0|max:20'
        ];
    }
}

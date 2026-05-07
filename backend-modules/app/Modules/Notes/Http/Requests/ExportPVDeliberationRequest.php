<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportPVDeliberationRequest extends FormRequest
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
            'semester' => 'required|integer|in:1,2'
        ];
    }
}

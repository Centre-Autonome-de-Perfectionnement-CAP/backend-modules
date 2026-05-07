<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDefaultGroupRequest extends FormRequest
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
            'study_level' => 'required|string',
            'cohort' => 'nullable|string',
        ];
    }
}

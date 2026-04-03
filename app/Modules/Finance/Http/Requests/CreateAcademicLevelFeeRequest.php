<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAcademicLevelFeeRequest extends FormRequest
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
            'study_level' => 'required|string|max:50',
            'registration_fee' => 'required|numeric|min:0',
            'uemoa_training_fee' => 'required|numeric|min:0',
            'non_uemoa_training_fee' => 'required|numeric|min:0',
            'exempted_training_fee' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}

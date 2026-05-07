<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicLevelFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'department_id' => 'sometimes|exists:departments,id',
            'study_level' => 'sometimes|string|max:50',
            'registration_fee' => 'sometimes|numeric|min:0',
            'uemoa_training_fee' => 'sometimes|numeric|min:0',
            'non_uemoa_training_fee' => 'sometimes|numeric|min:0',
            'exempted_training_fee' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

<?php

namespace App\Modules\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStudentsBySemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'department_id' => 'required|integer|exists:departments,id',
            'level' => 'required|string',
            'cohort' => 'required|string',
            'semester' => 'required|integer|in:1,2',
        ];
    }
}

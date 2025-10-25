<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitIngenieurSpecialiteDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required','string','max:100'],
            'department_id' => ['required','integer','exists:departments,id'],
            'academic_year_id' => ['required','integer','exists:academic_years,id'],
            'certificat_prepa' => ['required','file'],
        ];
    }
}

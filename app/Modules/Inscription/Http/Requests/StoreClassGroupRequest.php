<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'study_level' => ['required', 'string', 'max:255'],
            'replace_existing' => ['boolean'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:10'],
            'groups.*.student_ids' => ['required', 'array', 'min:1'],
            'groups.*.student_ids.*' => ['required', 'integer', 'exists:students,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'L\'année académique est requise',
            'academic_year_id.exists' => 'L\'année académique sélectionnée n\'existe pas',
            'department_id.required' => 'La filière est requise',
            'department_id.exists' => 'La filière sélectionnée n\'existe pas',
            'study_level.required' => 'Le niveau d\'études est requis',
            'groups.required' => 'Au moins un groupe est requis',
            'groups.min' => 'Au moins un groupe est requis',
            'groups.*.name.required' => 'Le nom du groupe est requis',
            'groups.*.student_ids.required' => 'La liste des étudiants est requise',
            'groups.*.student_ids.min' => 'Au moins un étudiant est requis par groupe',
            'groups.*.student_ids.*.exists' => 'Un ou plusieurs étudiants n\'existent pas',
        ];
    }
}

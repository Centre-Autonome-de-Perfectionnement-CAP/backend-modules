<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:course_elements,code',
            'credits' => 'required|integer|min:1',
            'teaching_unit_id' => 'required|exists:teaching_units,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
            'credits.required' => 'Le nombre de crédits est obligatoire.',
            'credits.min' => 'Le nombre de crédits doit être au moins 1.',
            'teaching_unit_id.required' => 'L\'unité d\'enseignement est obligatoire.',
            'teaching_unit_id.exists' => 'L\'unité d\'enseignement n\'existe pas.',
        ];
    }
}

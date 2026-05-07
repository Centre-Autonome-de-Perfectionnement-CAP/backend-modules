<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeachingUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:teaching_units,code',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
        ];
    }
}

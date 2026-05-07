<?php

namespace App\Modules\EmploiDuTemps\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:buildings,code',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du bâtiment est obligatoire.',
            'code.required' => 'Le code du bâtiment est obligatoire.',
            'code.unique' => 'Ce code de bâtiment existe déjà.',
        ];
    }
}

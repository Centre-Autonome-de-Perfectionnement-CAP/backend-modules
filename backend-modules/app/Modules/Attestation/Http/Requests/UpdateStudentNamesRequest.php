<?php

namespace App\Modules\Attestation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentNamesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => 'required|string|max:255',
            'first_names' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est requis',
            'first_names.required' => 'Les prénoms sont requis',
        ];
    }
}

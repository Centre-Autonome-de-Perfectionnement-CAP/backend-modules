<?php

namespace App\Modules\RH\Http\Requests;

use App\Services\ValidationService;
use Illuminate\Foundation\Http\FormRequest;

class CreateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'rib_number' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && !ValidationService::validateRIB($value)) {
                    $fail(ValidationService::getRIBErrorMessage());
                }
            }],
            'rib' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'ifu_number' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && !ValidationService::validateIFU($value)) {
                    $fail(ValidationService::getIFUErrorMessage());
                }
            }],
            'ifu' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bank' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est obligatoire',
            'first_name.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
        ];
    }
}

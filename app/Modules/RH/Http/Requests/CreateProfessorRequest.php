<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProfessorRequest extends FormRequest
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
            'email' => 'required|email|unique:professors,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
            'rib_number' => 'required|string|min:22|max:27',
            'rib' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ifu_number' => 'required|string|digits:13',
            'ifu' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank' => 'nullable|string|max:255',
            'statut' => 'nullable|in:active,inactive,suspended',
            'grade_id' => 'nullable|exists:grades,id',
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est obligatoire.',
            'first_name.required' => 'Le prénom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email existe déjà.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}

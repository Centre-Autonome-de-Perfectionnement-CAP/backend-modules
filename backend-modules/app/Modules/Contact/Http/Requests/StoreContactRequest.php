<?php

namespace App\Modules\Contact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Accessible publiquement
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être une adresse valide',
            'email.max' => 'L\'email ne doit pas dépasser 255 caractères',
            'subject.required' => 'Le sujet est obligatoire',
            'subject.max' => 'Le sujet ne doit pas dépasser 255 caractères',
            'message.required' => 'Le message est obligatoire',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne doit pas dépasser 5000 caractères',
        ];
    }
}

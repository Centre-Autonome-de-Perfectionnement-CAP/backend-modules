<?php

namespace App\Modules\Demandes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation métier est gérée dans TransitionService
    }

    public function rules(): array
    {
        return [
            'action'             => 'required|string|max:64',
            'motif'              => 'nullable|string|max:1000',
            'comment'            => 'nullable|string|max:1000',
            'signature_type'     => 'nullable|in:paraphe,signature',
            'chef_division_type' => 'nullable|in:formation_distance,formation_continue',
            'resend_to'          => 'nullable|string|max:32',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => "Le champ 'action' est obligatoire.",
        ];
    }
}

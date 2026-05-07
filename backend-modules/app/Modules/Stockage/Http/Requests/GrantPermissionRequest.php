<?php

namespace App\Modules\Stockage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'role_id' => 'nullable|integer|exists:roles,id',
            'permission_type' => 'required|in:read,write,delete,share,admin',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom validation rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Soit user_id, soit role_id doit être fourni, mais pas les deux
            if (!$this->user_id && !$this->role_id) {
                $validator->errors()->add('user_id', 'Vous devez fournir soit user_id, soit role_id.');
            }
            
            if ($this->user_id && $this->role_id) {
                $validator->errors()->add('user_id', 'Vous ne pouvez pas fournir à la fois user_id et role_id.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
            'role_id.exists' => 'Le rôle spécifié n\'existe pas.',
            'permission_type.required' => 'Le type de permission est requis.',
            'permission_type.in' => 'Le type de permission doit être read, write, delete, share ou admin.',
            'expires_at.after' => 'La date d\'expiration doit être dans le futur.',
        ];
    }
}

<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('adminUser');
        
        return [
            'last_name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string|max:20',
            'rib_number' => 'nullable|string|max:255',
            'rib' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'ifu_number' => 'nullable|string|max:255',
            'ifu' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bank' => 'nullable|string|max:255',
        ];
    }
}

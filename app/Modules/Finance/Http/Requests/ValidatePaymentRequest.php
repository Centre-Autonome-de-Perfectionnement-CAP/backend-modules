<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidatePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'observation' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères',
        ];
    }
}
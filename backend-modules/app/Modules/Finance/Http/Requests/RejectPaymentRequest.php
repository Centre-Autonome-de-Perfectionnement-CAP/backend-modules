<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'motif' => 'required|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'motif.required' => 'Le motif de rejet est obligatoire',
            'motif.max' => 'Le motif ne peut pas dépasser 1000 caractères',
        ];
    }
}
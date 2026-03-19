<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDefensestatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:pending,accepted,rejected,scheduled,completed',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le status est requis',
            'status.in' => 'Le status doit être: pending, accepted, rejected, scheduled ou completed',
            'rejection_reason.required_if' => 'La raison du rejet est requise lorsque le status est rejeté',
        ];
    }
}

<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDefenseStatusRequest extends FormRequest
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
            'status.required' => 'Le statut est requis',
            'status.in' => 'Le statut doit être: pending, accepted, rejected, scheduled ou completed',
            'rejection_reason.required_if' => 'La raison du rejet est requise lorsque le statut est rejeté',
        ];
    }
}

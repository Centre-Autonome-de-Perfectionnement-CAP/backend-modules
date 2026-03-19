<?php

namespace App\Modules\Stockage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
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
            'collection' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }
}

<?php

namespace App\Modules\Stockage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
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
            'file' => 'required|file|max:51200', // 50MB max
            'visibility' => 'nullable|in:public,private',
            'collection' => 'nullable|string|max:255',
            'module_name' => 'nullable|string|max:255',
            'module_resource_type' => 'nullable|string|max:255',
            'module_resource_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier est requis.',
            'file.file' => 'Le fichier doit être un fichier valide.',
            'file.max' => 'Le fichier ne doit pas dépasser 50MB.',
            'visibility.in' => 'La visibilité doit être "public" ou "private".',
        ];
    }
}

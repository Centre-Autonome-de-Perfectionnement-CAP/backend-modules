<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'file' => 'nullable|file|max:10240',
            'lien' => 'nullable|url',
            'datePublication' => 'sometimes|date',
            'categorie' => 'sometimes|in:administratif,pedagogique,legal,organisation',
        ];
    }
}

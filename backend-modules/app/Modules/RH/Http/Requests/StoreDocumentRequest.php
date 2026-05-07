<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'file' => 'required_without:lien|file|max:10240',
            'lien' => 'required_without:file|url',
            'datePublication' => 'required|date',
            'categorie' => 'required|in:administratif,pedagogique,legal,organisation',
        ];
    }
}

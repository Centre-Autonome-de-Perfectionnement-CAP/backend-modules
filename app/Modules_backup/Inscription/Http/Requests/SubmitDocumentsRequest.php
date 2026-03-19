<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDocumentsRequest extends FormRequest
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
            'documents' => 'required|array|min:1|max:10',
            'documents.*' => 'required|file|max:51200|mimes:pdf,doc,docx,jpg,jpeg,png', // 50MB max, types spécifiques
            'document_types' => 'required|array|min:1',
            'document_types.*' => 'required|string|in:diploma,certificate,transcript,photo,id_card,other',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'documents.required' => 'Au moins un document doit être fourni.',
            'documents.array' => 'Les documents doivent être un tableau.',
            'documents.min' => 'Au moins un document doit être fourni.',
            'documents.max' => 'Maximum 10 documents peuvent être soumis.',
            'documents.*.required' => 'Chaque document est requis.',
            'documents.*.file' => 'Chaque élément doit être un fichier.',
            'documents.*.max' => 'Chaque fichier ne peut pas dépasser 50MB.',
            'documents.*.mimes' => 'Les fichiers doivent être de type PDF, DOC, DOCX, JPG, JPEG, ou PNG.',
            'document_types.required' => 'Les types de documents sont requis.',
            'document_types.array' => 'Les types de documents doivent être un tableau.',
            'document_types.min' => 'Au moins un type de document doit être fourni.',
            'document_types.*.required' => 'Chaque type de document est requis.',
            'document_types.*.string' => 'Chaque type de document doit être une chaîne de caractères.',
            'document_types.*.in' => 'Le type de document doit être l\'un des suivants : diploma, certificate, transcript, photo, id_card, other.',
        ];
    }
}

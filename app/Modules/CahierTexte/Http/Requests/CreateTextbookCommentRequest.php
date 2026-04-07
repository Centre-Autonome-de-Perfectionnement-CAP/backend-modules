<?php

namespace App\Modules\CahierTexte\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTextbookCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => 'required|string',
            'type' => 'nullable|in:comment,suggestion,correction',
            'parent_id' => 'nullable|exists:textbook_comments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Le commentaire est requis',
            'type.in' => 'Le type doit être comment, suggestion ou correction',
            'parent_id.exists' => 'Le commentaire parent n\'existe pas',
        ];
    }
}

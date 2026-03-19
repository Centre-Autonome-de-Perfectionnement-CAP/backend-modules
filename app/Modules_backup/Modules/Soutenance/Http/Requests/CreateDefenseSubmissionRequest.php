<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDefenseSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->defense_submission_period_id === '' || $this->defense_submission_period_id === 'NaN') {
            $this->merge(['defense_submission_period_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'last_name' => 'required|string|max:255',
            'first_names' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contacts' => 'required|array',
            'contacts.*' => 'required|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'student_id_number' => 'nullable|string|max:11',
            'defense_submission_period_id' => 'sometimes|nullable|exists:defense_submission_periods,id',
            'thesis_title' => 'required|string',
            'professor_id' => 'required',
            'defense_type' => 'required|string|in:licence,master',
            'thesis_file' => 'required|file|mimes:pdf|max:10240',
            'additional_files' => 'sometimes|array',
            'additional_files.*' => 'file|mimes:pdf,doc,docx|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est requis',
            'first_names.required' => 'Les prénoms sont requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'contacts.required' => 'Le contact est requis',
            'department_id.required' => 'Le département est requis',
            'department_id.exists' => 'Le département sélectionné n\'existe pas',
            'student_id_number.required' => 'Le matricule est requis',
            'defense_submission_period_id.required' => 'La période de soumission est requise',
            'defense_submission_period_id.exists' => 'La période de soumission sélectionnée n\'existe pas',
            'thesis_title.required' => 'Le titre de la thèse est requis',
            'professor_id.required' => 'Le professeur encadreur est requis',
            'professor_id.exists' => 'Le professeur sélectionné n\'existe pas',
            'defense_type.required' => 'Le type de soutenance est requis',
            'defense_type.in' => 'Le type de soutenance doit être: licence ou master',
            'thesis_file.mimes' => 'Le fichier de thèse doit être au format PDF',
            'thesis_file.max' => 'Le fichier de thèse ne doit pas dépasser 10 Mo',
            'additional_files.*.mimes' => 'Les fichiers supplémentaires doivent être au format PDF, DOC ou DOCX',
            'additional_files.*.max' => 'Chaque fichier supplémentaire ne peut pas dépasser 5 Mo',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

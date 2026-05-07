<?php

namespace App\Modules\CahierTexte\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTextbookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'sometimes|exists:programs,id',
            'scheduled_course_id' => 'nullable|exists:scheduled_courses,id',
            'session_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'hours_taught' => 'sometimes|numeric|min:0|max:24',
            'session_title' => 'sometimes|string|max:255',
            'content_covered' => 'sometimes|string',
            'objectives' => 'nullable|string',
            'teaching_methods' => 'nullable|string',
            'homework' => 'nullable|string',
            'homework_due_date' => 'nullable|date',
            'resources' => 'nullable|array',
            'resources.*' => 'string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'integer',
            'students_present' => 'nullable|integer|min:0',
            'students_absent' => 'nullable|integer|min:0',
            'observations' => 'nullable|string',
            'status' => 'nullable|in:draft,published,validated',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.exists' => 'Le programme sélectionné n\'existe pas',
            'session_date.date' => 'La date de la séance doit être une date valide',
            'start_time.date_format' => 'L\'heure de début doit être au format HH:MM',
            'end_time.date_format' => 'L\'heure de fin doit être au format HH:MM',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début',
            'hours_taught.numeric' => 'Le nombre d\'heures doit être un nombre',
            'hours_taught.min' => 'Le nombre d\'heures doit être positif',
            'hours_taught.max' => 'Le nombre d\'heures ne peut pas dépasser 24',
            'session_title.max' => 'Le titre ne peut pas dépasser 255 caractères',
            'status.in' => 'Le statut doit être draft, published ou validated',
        ];
    }
}

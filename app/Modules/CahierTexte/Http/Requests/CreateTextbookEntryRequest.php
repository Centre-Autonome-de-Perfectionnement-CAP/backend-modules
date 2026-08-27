<?php

namespace App\Modules\CahierTexte\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTextbookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',
            'scheduled_course_id' => 'nullable|exists:scheduled_courses,id',
            'emploi_du_temps_id' => 'required|exists:emploi_du_temps,id',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'hours_taught' => 'required|numeric|min:0|max:24',
            'session_title' => 'required|string|max:255',
            'content_covered' => 'required|string',
            'objectives' => 'nullable|string',
            'teaching_methods' => 'nullable|string',
            'homework' => 'nullable|string',
            'homework_due_date' => 'nullable|date|after_or_equal:session_date',
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
            'program_id.required' => 'Le programme est requis',
            'program_id.exists' => 'Le programme sélectionné n\'existe pas',
            'emploi_du_temps_id.required' => 'La séance d\'emploi du temps est requise',
            'emploi_du_temps_id.exists' => 'La séance d\'emploi du temps sélectionnée n\'existe pas',
            'session_date.required' => 'La date de la séance est requise',
            'session_date.date' => 'La date de la séance doit être une date valide',
            'start_time.required' => 'L\'heure de début est requise',
            'start_time.date_format' => 'L\'heure de début doit être au format HH:MM',
            'end_time.required' => 'L\'heure de fin est requise',
            'end_time.date_format' => 'L\'heure de fin doit être au format HH:MM',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début',
            'hours_taught.required' => 'Le nombre d\'heures enseignées est requis',
            'hours_taught.numeric' => 'Le nombre d\'heures doit être un nombre',
            'hours_taught.min' => 'Le nombre d\'heures doit être positif',
            'hours_taught.max' => 'Le nombre d\'heures ne peut pas dépasser 24',
            'session_title.required' => 'Le titre de la séance est requis',
            'session_title.max' => 'Le titre ne peut pas dépasser 255 caractères',
            'content_covered.required' => 'Le contenu couvert est requis',
            'homework_due_date.after_or_equal' => 'La date limite des devoirs doit être après la date de la séance',
            'status.in' => 'Le statut doit être draft, published ou validated',
        ];
    }

    /**
     * AJOUT — règle métier : une entrée de cahier de texte ne peut être
     * créée que si la séance (date + heure de début) a commencé il y a au
     * moins 1 heure. Évite qu'un responsable de classe remplisse un
     * cahier de texte pour un cours qui n'a pas encore eu lieu.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sessionDate = $this->input('session_date');
            $startTime   = $this->input('start_time');

            if (!$sessionDate || !$startTime) {
                return;
            }

            try {
                $sessionStart = \Illuminate\Support\Carbon::parse("{$sessionDate} {$startTime}");
            } catch (\Exception $e) {
                return; // format déjà rejeté par les règles ci-dessus
            }

            if (!$sessionStart->isPast() || $sessionStart->diffInMinutes(now()) < 60) {
                $validator->errors()->add(
                    'session_date',
                    'Le cahier de texte ne peut être rempli qu\'au moins 1 heure après le début de la séance.'
                );
            }
        });
    }
}

<?php

namespace App\Modules\EmploiDuTemps\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduledCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'sometimes|required|exists:programs,id',
            'time_slot_id' => 'sometimes|required|exists:time_slots,id',
            'room_id' => 'sometimes|required|exists:rooms,id',
            'start_date' => 'sometimes|required|date',
            'total_hours' => 'sometimes|required|numeric|min:0.5|max:1000',
            'hours_completed' => 'sometimes|numeric|min:0',
            'is_recurring' => 'boolean',
            'recurrence_end_date' => 'nullable|date|after:start_date',
            'excluded_dates' => 'nullable|array',
            'excluded_dates.*' => 'date',
            'notes' => 'nullable|string|max:1000',
            'is_cancelled' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => 'Le programme de cours est obligatoire.',
            'program_id.exists' => 'Le programme sélectionné n\'existe pas.',
            'time_slot_id.required' => 'Le créneau horaire est obligatoire.',
            'time_slot_id.exists' => 'Le créneau horaire sélectionné n\'existe pas.',
            'room_id.required' => 'La salle est obligatoire.',
            'room_id.exists' => 'La salle sélectionnée n\'existe pas.',
            'start_date.required' => 'La date de début est obligatoire.',
            'total_hours.required' => 'La masse horaire totale est obligatoire.',
            'total_hours.min' => 'La masse horaire doit être au minimum 0.5 heure.',
            'recurrence_end_date.after' => 'La date de fin de récurrence doit être après la date de début.',
        ];
    }
}

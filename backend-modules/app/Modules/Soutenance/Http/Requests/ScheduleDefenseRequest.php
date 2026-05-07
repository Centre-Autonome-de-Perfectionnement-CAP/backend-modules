<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleDefenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'defense_date' => 'required|date|after:now',
            'room_id' => 'required|exists:rooms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'defense_date.required' => 'La date de soutenance est requise',
            'defense_date.date' => 'La date de soutenance doit être une date valide',
            'defense_date.after' => 'La date de soutenance doit être dans le futur',
            'room_id.required' => 'La salle est requise',
            'room_id.exists' => 'La salle sélectionnée n\'existe pas',
        ];
    }
}

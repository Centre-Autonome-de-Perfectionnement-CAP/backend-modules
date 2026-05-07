<?php

namespace App\Modules\EmploiDuTemps\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\EmploiDuTemps\Models\TimeSlot;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'sometimes|required|in:' . implode(',', [
                TimeSlot::DAY_MONDAY,
                TimeSlot::DAY_TUESDAY,
                TimeSlot::DAY_WEDNESDAY,
                TimeSlot::DAY_THURSDAY,
                TimeSlot::DAY_FRIDAY,
                TimeSlot::DAY_SATURDAY,
                TimeSlot::DAY_SUNDAY,
            ]),
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'type' => 'sometimes|required|in:' . implode(',', [
                TimeSlot::TYPE_LECTURE,
                TimeSlot::TYPE_TD,
                TimeSlot::TYPE_TP,
                TimeSlot::TYPE_EXAM,
            ]),
            'name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'Le jour de la semaine est obligatoire.',
            'start_time.required' => 'L\'heure de début est obligatoire.',
            'start_time.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'end_time.required' => 'L\'heure de fin est obligatoire.',
            'end_time.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'type.required' => 'Le type de créneau est obligatoire.',
        ];
    }
}

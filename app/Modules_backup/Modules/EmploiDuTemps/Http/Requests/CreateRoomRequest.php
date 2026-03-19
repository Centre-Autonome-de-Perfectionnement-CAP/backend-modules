<?php

namespace App\Modules\EmploiDuTemps\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\EmploiDuTemps\Models\Room;

class CreateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'building_id' => 'required|exists:buildings,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rooms,code',
            'capacity' => 'required|integer|min:1|max:10000',
            'room_type' => 'required|in:' . implode(',', [
                Room::TYPE_AMPHITHEATER,
                Room::TYPE_CLASSROOM,
                Room::TYPE_LAB,
                Room::TYPE_COMPUTER_LAB,
                Room::TYPE_CONFERENCE,
            ]),
            'equipment' => 'nullable|array',
            'equipment.*' => 'string',
            'is_available' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'building_id.required' => 'Le bâtiment est obligatoire.',
            'building_id.exists' => 'Le bâtiment sélectionné n\'existe pas.',
            'name.required' => 'Le nom de la salle est obligatoire.',
            'code.required' => 'Le code de la salle est obligatoire.',
            'code.unique' => 'Ce code de salle existe déjà.',
            'capacity.required' => 'La capacité est obligatoire.',
            'capacity.min' => 'La capacité doit être au moins 1.',
            'room_type.required' => 'Le type de salle est obligatoire.',
        ];
    }
}

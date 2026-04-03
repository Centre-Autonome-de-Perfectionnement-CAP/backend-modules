<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeachingUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teachingUnitId = $this->route('teaching_unit');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:teaching_units,code,' . $teachingUnitId,
        ];
    }
}

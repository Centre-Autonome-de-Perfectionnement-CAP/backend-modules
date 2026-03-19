<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseElementId = $this->route('course_element');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:course_elements,code,' . $courseElementId,
            'credits' => 'sometimes|required|integer|min:1',
            'teaching_unit_id' => 'sometimes|required|exists:teaching_units,id',
        ];
    }
}

<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyProgramsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_class_group_id' => 'required|exists:class_groups,id',
            'target_class_group_id' => 'required|exists:class_groups,id|different:source_class_group_id',
        ];
    }

    public function messages(): array
    {
        return [
            'source_class_group_id.required' => 'Le groupe de classe source est obligatoire.',
            'source_class_group_id.exists' => 'Le groupe de classe source n\'existe pas.',
            'target_class_group_id.required' => 'Le groupe de classe cible est obligatoire.',
            'target_class_group_id.exists' => 'Le groupe de classe cible n\'existe pas.',
            'target_class_group_id.different' => 'Le groupe de classe cible doit être différent du groupe source.',
        ];
    }

    public function attributes(): array
    {
        return [
            'source_class_group_id' => 'groupe de classe source',
            'target_class_group_id' => 'groupe de classe cible',
        ];
    }
}

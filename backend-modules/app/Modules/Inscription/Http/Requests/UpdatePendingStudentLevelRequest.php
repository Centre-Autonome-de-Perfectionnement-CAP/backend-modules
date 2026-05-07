<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePendingStudentLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => 'required|string|max:10',
        ];
    }
}

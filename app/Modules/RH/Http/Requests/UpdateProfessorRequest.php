<?php

namespace App\Modules\RH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $professorId = $this->route('professor')?->id;

        return [
            'last_name'    => 'sometimes|required|string|max:255',
            'first_name'   => 'sometimes|required|string|max:255',
            'email'        => 'sometimes|required|email|unique:professors,email,' . $professorId,
            'phone'        => 'nullable|string|max:20',
            'password'     => 'nullable|string|min:8',
            'role_id'      => 'nullable|exists:roles,id',
            'rib_number'   => 'nullable|string|max:27',
            'rib'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ifu_number'   => 'nullable|string|max:13',
            'ifu'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank'         => 'nullable|string|max:255',
            'status'       => 'nullable|in:active,inactive,suspended', // ✅ corrigé : statut → status
            'grade_id'     => 'nullable|exists:grades,id',
            'speciality'   => 'nullable|string|max:255',  // ✅ ajouté
            'bio'          => 'nullable|string',           // ✅ ajouté
            'nationality'  => 'nullable|string|max:100',   // ✅ ajouté
            'profession'   => 'nullable|string|max:100',   // ✅ ajouté
            'city'         => 'nullable|string|max:100',   // ✅ ajouté
            'district'     => 'nullable|string|max:100',   // ✅ ajouté
            'plot_number'  => 'nullable|string|max:100',   // ✅ ajouté
            'house_number' => 'nullable|string|max:100',   // ✅ ajouté
        ];
    }
}

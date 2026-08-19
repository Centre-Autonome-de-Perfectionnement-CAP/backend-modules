<?php

namespace App\Modules\LegacyStudent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegacyStudentRequest extends FormRequest
{
    /**
     * Endpoint protégé par le middleware auth:sanctum sur la route.
     * Ici on pourrait affiner avec une policy/permission plus tard
     * (ex: seule la secrétaire ou l'admin peut éditer).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // {id} est un segment de route, disponible via route('id')
        $legacyStudentId = $this->route('id');

        return [
            // 'sometimes' : la secrétaire peut ne corriger qu'un seul champ
            'matricule' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('legacy_students', 'matricule')->ignore($legacyStudentId),
            ],

            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'required', 'string', 'max:100'],

            'email' => ['sometimes', 'required', 'email', 'max:150'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],

            'enrollment_year' => ['sometimes', 'required', 'integer', 'min:1970', 'max:2022'],

            'department_ids'   => ['sometimes', 'required', 'array', 'min:1'],
            'department_ids.*' => ['string', 'uuid', 'exists:departments,id'],

            'notes_admin' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.unique'          => 'Ce matricule est déjà utilisé par un autre dossier.',
            'email.email'               => 'L\'adresse email n\'est pas valide.',
            'enrollment_year.max'       => 'Cette inscription concerne uniquement les étudiants inscrits avant 2023.',
            'department_ids.min'        => 'Vous devez sélectionner au moins une filière.',
            'department_ids.*.exists'   => 'Une des filières sélectionnées est invalide.',
        ];
    }

}
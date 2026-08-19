<?php

namespace App\Modules\LegacyStudent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterLegacyStudentRequest extends FormRequest
{
    /**
     * Endpoint public : tout le monde (étudiant non authentifié) peut soumettre.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string', 'max:50'],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],

            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],

            // année d'inscription strictement < 2023, cf. règle métier du doc
            'enrollment_year' => ['required', 'integer', 'min:1970', 'max:2022'],

            'department_ids'   => ['required', 'array', 'min:1'],
            'department_ids.*' => ['string', 'uuid', 'exists:departments,id'],
        ];
    }

    /**
     * Messages en français, adaptés à l'utilisateur final (étudiant).
     */
    public function messages(): array
    {
        return [
            'matricule.required'        => 'Le matricule est obligatoire.',
            'first_name.required'       => 'Le prénom est obligatoire.',
            'last_name.required'        => 'Le nom de famille est obligatoire.',
            'email.required'            => 'L\'adresse email est obligatoire.',
            'email.email'               => 'L\'adresse email n\'est pas valide.',
            'phone.required'            => 'Le numéro de téléphone est obligatoire.',
            'enrollment_year.required'  => 'L\'année d\'inscription est obligatoire.',
            'enrollment_year.max'       => 'Cette inscription concerne uniquement les étudiants inscrits avant 2023.',
            'department_ids.required'   => 'Vous devez sélectionner au moins une filière.',
            'department_ids.min'        => 'Vous devez sélectionner au moins une filière.',
            'department_ids.*.exists'   => 'Une des filières sélectionnées est invalide.',
        ];
    }

}
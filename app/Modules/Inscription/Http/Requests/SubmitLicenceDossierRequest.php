<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitLicenceDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required','string','max:255'],
            'first_names' => ['required','string','max:255'],
            'email' => ['required','email','unique:personal_information,email'],
            'birth_date' => ['required','date'],
            'birth_place' => ['required','string','max:255'],
            'birth_country' => ['required','string','max:255'],
            'gender' => ['required','in:M,F'],
            'contacts' => ['required','array'],

            'study_level' => ['required','string'],
            'entry_diploma_id' => ['nullable','integer','exists:entry_diplomas,id'],
            'academic_year_id' => ['required','integer','exists:academic_years,id'],
            'department_id' => ['required','integer','exists:departments,id'],

            'demande_da' => ['required','file'],
            'cv' => ['required','file'],
            'acte_naissance' => ['required','file'],
            'diplome_bac' => ['required','file'],
            'diplome_licence' => ['nullable','file'],
            'attestation_travail' => ['required','file'],
            'quittance_rectorat' => ['required','file'],
            'quittance_cap' => ['required','file'],
            'attestation_depot_dossier' => ['nullable','file'],
            'photo' => ['nullable','file','image'],
        ];
    }
}

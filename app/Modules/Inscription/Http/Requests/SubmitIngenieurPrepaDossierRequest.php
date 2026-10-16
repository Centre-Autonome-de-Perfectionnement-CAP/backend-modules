<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitIngenieurPrepaDossierRequest extends FormRequest
{
    use \App\Modules\Inscription\Http\Requests\Concerns\DossierUploadRules;

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

            'demande_da' => $this->pieceRule(),
            'cv' => $this->pieceRule(),
            'acte_naissance' => $this->pieceRule(),
            'diplome_bac' => $this->pieceRule(),
            'diplome_licence' => $this->pieceRule(),
            'attestation_travail' => $this->pieceRule(),
            'quittance_rectorat' => $this->pieceRule(false),
            'quittance_cap' => $this->pieceRule(),
            'attestation_depot_dossier' => $this->pieceRule(false),
            'photo' => $this->photoRule(),
        ];
    }

    public function messages(): array
    {
        return $this->uploadMessages();
    }
}

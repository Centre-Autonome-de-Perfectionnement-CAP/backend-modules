<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'statut' => 'required|string|max:255',
            'titre' => 'required|string|max:255',
            'nometu' => 'required|string|max:255',
            'prenometu' => 'required|string|max:255',
            'diplome' => 'required|string|max:255',
            'date_soutenance' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est requis',
            'prenom.required' => 'Le prénom est requis',
            'statut.required' => 'Le statut est requis',
            'titre.required' => 'Le titre est requis',
            'nometu.required' => 'Le nom de l\'étudiant est requis',
            'prenometu.required' => 'Le prénom de l\'étudiant est requis',
            'diplome.required' => 'Le diplôme est requis',
            'date_soutenance.required' => 'La date de soutenance est requise',
            'date_soutenance.date' => 'La date de soutenance doit être une date valide',
        ];
    }
}

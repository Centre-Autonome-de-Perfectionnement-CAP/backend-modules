<?php

namespace App\Modules\Soutenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQuitusRequest extends FormRequest
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
            'titre' => 'required|string|max:255',
            'diplome' => 'required|string|max:255',
            'nometu' => 'required|string|max:255',
            'prenometu' => 'required|string|max:255',
            'grade' => 'required|string|max:255',
            'filiere' => 'required|string|max:255',
            'intitule' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est requis',
            'prenom.required' => 'Le prénom est requis',
            'titre.required' => 'Le titre est requis',
            'diplome.required' => 'Le diplôme est requis',
            'nometu.required' => 'Le nom de l\'étudiant est requis',
            'prenometu.required' => 'Le prénom de l\'étudiant est requis',
            'grade.required' => 'Le grade est requis',
            'filiere.required' => 'La filière est requise',
            'intitule.required' => 'L\'intitulé est requis',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

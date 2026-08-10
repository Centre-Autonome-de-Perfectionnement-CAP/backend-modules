<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use App\Modules\Inscription\Models\Student;

class CreatePaiementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // L'API n'est pas protégée par authentification
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matricule' => [
                'required',
                'string',
                'max:11',
                function ($attribute, $value, $fail) {
                    $student = Student::where('student_id_number', $value)->first();
                    if (!$student) {
                        $fail('Le matricule fourni n\'existe pas dans notre base de données.');
                    }
                },
            ],
            'montant' => 'nullable|numeric|min:0',
            'reference' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = \App\Modules\Finance\Models\Paiement::where('reference', $value)
                        ->whereIn('status', ['pending', 'approved'])
                        ->exists();
                    
                    if ($exists) {
                        $fail('Cette référence existe déjà dans le système.');
                    }
                },
            ],
            'department_id' => 'required|integer|exists:departments,id',
            'numero_compte' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value !== '01711043903') {
                        $fail('Le numéro de compte fourni n\'est pas valide. Veuillez utiliser le compte officiel de l\'établissement.');
                    }
                },
            ],
            'date_versement' => 'nullable|date|before_or_equal:today',
            'quittance' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'motif' => 'required|string',
            'email' => 'nullable|email|max:255',
            'contact' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.string' => 'Le matricule doit être une chaîne de caractères.',
            'matricule.max' => 'Le matricule ne peut pas dépasser 11 caractères.',
            
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur ou égal à 0.',
            
            'reference.required' => 'La référence est obligatoire.',
            'reference.string' => 'La référence doit être une chaîne de caractères.',
            'reference.max' => 'La référence ne peut pas dépasser 255 caractères.',
            'reference.unique' => 'Cette référence existe déjà dans le système.',
            
            'numero_compte.required' => 'Le numéro de compte est obligatoire.',
            'numero_compte.string' => 'Le numéro de compte doit être une chaîne de caractères.',
            'numero_compte.max' => 'Le numéro de compte ne peut pas dépasser 255 caractères.',
            
            'date_versement.date' => 'La date de versement doit être une date valide.',
            'date_versement.before_or_equal' => 'La date de versement ne peut pas être dans le futur.',
            
            'quittance.required' => 'La quittance est obligatoire.',
            'quittance.file' => 'La quittance doit être un fichier.',
            'quittance.mimes' => 'La quittance doit être un fichier de type: pdf, jpg, jpeg ou png.',
            'quittance.max' => 'La quittance ne peut pas dépasser 5 Mo.',
            
            'motif.required' => 'Le motif est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            
            'department_id.required' => 'Le département est obligatoire.',
            'department_id.integer' => 'Le département doit être un identifiant valide.',
            'department_id.exists' => 'Le département sélectionné n\'existe pas.',
            
            'email.email' => 'L\'adresse email doit être valide.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
            
            'contact.string' => 'Le contact doit être une chaîne de caractères.',
            'contact.max' => 'Le contact ne peut pas dépasser 255 caractères.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'Erreur de validation des données.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

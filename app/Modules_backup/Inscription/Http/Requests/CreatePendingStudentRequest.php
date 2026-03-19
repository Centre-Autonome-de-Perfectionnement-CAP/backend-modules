<?php

namespace App\Modules\Inscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePendingStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'email' => 'required|email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'entry_diploma_id' => 'required|exists:entry_diplomas,id',
        ];

        // Pour la création, vérifier l'unicité de l'email
        if ($this->isMethod('post')) {
            $rules['email'] .= '|unique:pending_students,email';
        }

        // Pour la mise à jour, permettre la mise à jour du status et du sponsoring
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['status'] = 'sometimes|in:pending,approved,rejected,withdrawn';
            $rules['sponsorise'] = 'sometimes|in:Oui,Non';
            $rules['department_id'] = 'sometimes|exists:departments,id';
            $rules['academic_year_id'] = 'sometimes|exists:academic_years,id';
            $rules['level'] = 'sometimes|string|max:10';
            $rules['cuca_opinion'] = 'sometimes|in:favorable,defavorable,pending';
            $rules['cuca_comment'] = 'nullable|string';
            $rules['cuo_opinion'] = 'sometimes|in:favorable,defavorable,pending';
            $rules['rejection_reason'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'department_id.exists' => 'Le département sélectionné n\'existe pas.',
            'academic_year_id.exists' => 'L\'année académique sélectionnée n\'existe pas.',
            'entry_diploma_id.required' => 'Le diplôme d\'entrée est requis.',
            'entry_diploma_id.exists' => 'Le diplôme d\'entrée sélectionné n\'existe pas.',
            'status.in' => 'Le status doit être l\'un des suivants : pending, approved, rejected, withdrawn.',
            'sponsorise.in' => 'Le sponsoring doit être "Oui" ou "Non".',
            'cuca_opinion.in' => 'L\'opinion CUCA doit être favorable, défavorable ou en attente.',
            'cuo_opinion.in' => 'L\'opinion CUO doit être favorable, défavorable ou en attente.',
        ];
    }
}

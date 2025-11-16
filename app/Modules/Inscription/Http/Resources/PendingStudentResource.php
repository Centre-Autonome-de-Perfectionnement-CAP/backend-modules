<?php

namespace App\Modules\Inscription\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingStudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Extraire le phone depuis le JSON contacts si disponible
        $phone = null;
        if ($this->personalInformation && $this->personalInformation->contacts) {
            $contacts = is_array($this->personalInformation->contacts) 
                ? $this->personalInformation->contacts 
                : json_decode($this->personalInformation->contacts, true);
            // Contacts peut être un array de numéros ou un objet avec clé 'phone'
            if (is_array($contacts)) {
                $phone = $contacts['phone'] ?? $contacts[0] ?? null;
            }
        }

        $documents = [];
        if ($this->documents) {
            foreach ($this->documents as $name => $value) {
                $documents[$name] = url("/api/inscription/files/legacy?path=" . urlencode($value));
            }
        }

        return [
            'id' => $this->id,
            'email' => $this->personalInformation?->email,
            'first_name' => $this->personalInformation?->first_names,
            'last_name' => $this->personalInformation?->last_name,
            'phone' => $phone,
            'gender' => $this->personalInformation?->gender,
            'status' => $this->cuca_opinion ?? 'pending',
            'documents' => $documents,
            'submitted_at' => $this->created_at?->toISOString(),
            'department' => $this->department?->name,
            'exonere' => $this->exonere === 'Oui' ? 'Oui' : 'Non',
            'sponsorise' => $this->sponsorise === 'Oui' ? 'Oui' : 'Non',
            'opinionCuca' => $this->cuca_opinion,
            'commentaireCuca' => $this->cuca_comment,
            'opinionCuo' => $this->cuo_opinion,
            'commentaireCuo' => null,
            'mailCucaEnvoye' => 'Non',
            'mailCucaCount' => 0,
            'mailCuoEnvoye' => 'Non',
            'mailCuoCount' => 0,
            'entry_diploma' => $this->whenLoaded('entryDiploma', function () {
                return [
                    'id' => $this->entryDiploma->id,
                    'name' => $this->entryDiploma->name,
                    'abbreviation' => $this->entryDiploma->abbreviation,
                    'entry_level' => $this->entryDiploma->entry_level,
                ];
            }),
            'student_pending_students' => $this->whenLoaded('studentPendingStudents', function () {
                return $this->studentPendingStudents->map(function ($sps) {
                    return [
                        'id' => $sps->id,
                        'student' => $sps->whenLoaded('student', function () {
                            return [
                                'id' => $sps->student->id,
                                'student_id_number' => $sps->student->student_id_number,
                            ];
                        }),
                    ];
                });
            }),

        ];
    }
}

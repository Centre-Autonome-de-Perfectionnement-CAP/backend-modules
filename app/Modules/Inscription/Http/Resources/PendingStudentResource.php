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
                // Essayer différentes clés possibles
                $phone = $contacts['phone'] 
                    ?? $contacts['telephone'] 
                    ?? $contacts['mobile'] 
                    ?? $contacts['tel'] 
                    ?? $contacts[0] 
                    ?? null;
            }
        }

        $documents = [];
        if ($this->documents) {
            foreach ($this->documents as $name => $value) {
<<<<<<< HEAD
                // Gérer le format objet {url, custom_name} ou string
                $url = is_array($value) ? ($value['url'] ?? '') : $value;
                $customName = is_array($value) ? ($value['custom_name'] ?? null) : null;
                
                $path = str_starts_with($url, 'public/') ? $url : 'public/' . $url;
                $documents[$name] = [
                    'url' => url("/api/inscription/files/legacy?path=" . urlencode($path)),
                    'custom_name' => $customName
                ];
=======
                // Ajouter 'public/' au chemin si nécessaire
                $path = str_starts_with($value, 'public/') ? $value : 'public/' . $value;
                $documents[$name] = url("/api/inscription/files/legacy?path=" . urlencode($path));
>>>>>>> eea2b06 (draft)
            }
        }

        // Utiliser le statut réel de la base de données
        $status = $this->status ?? 'pending';

        return [
            'id' => $this->id,
            'email' => $this->personalInformation?->email,
            'first_name' => $this->personalInformation?->first_names,
            'last_name' => $this->personalInformation?->last_name,
            'phone' => $phone,
            'gender' => $this->personalInformation?->gender,
            'status' => $status,
            'documents' => $documents,
            'submitted_at' => $this->created_at?->toISOString(),
            'department' => $this->department?->name,
            'exonere' => $this->exonere === 'Oui' ? 'Oui' : 'Non',
            'sponsorise' => $this->sponsorise === 'Oui' ? 'Oui' : 'Non',
            'opinionCuca' => $this->cuca_opinion ? ucfirst(strtolower($this->cuca_opinion)) : 'pending',
            'commentaireCuca' => $this->cuca_comment,
            'opinionCuo' => $this->cuo_opinion ? ucfirst(strtolower($this->cuo_opinion)) : null,
            'commentaireCuo' => $this->cuo_comment,
            'mailCucaEnvoye' => $this->mail_cuca_sent ? 'Oui' : 'Non',
            'mailCucaCount' => $this->mail_cuca_count ?? 0,
            'mailCuoEnvoye' => $this->mail_cuo_sent ? 'Oui' : 'Non',
            'mailCuoCount' => $this->mail_cuo_count ?? 0,
<<<<<<< HEAD
            'level' => $this->level,
=======
>>>>>>> eea2b06 (draft)
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

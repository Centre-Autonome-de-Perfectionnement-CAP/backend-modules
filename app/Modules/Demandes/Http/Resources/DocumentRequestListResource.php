<?php

namespace App\Modules\Demandes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CORRECTIF (v2) — B3.1 : API Resource pour le listing
 *
 * Les champs ci-dessous correspondent EXACTEMENT aux colonnes/sous-requêtes
 * réellement sélectionnées par DocumentRequestQueryService::listing()
 * (BASE_COLUMNS + historySubqueries() + MATRICULE_SUBQUERY), vérifiées
 * dans le code source réel. Aucun champ inventé.
 *
 * Usage : optionnel, en remplacement de `return $demandes` brut dans
 * DocumentRequestController::index(). N'est PAS appliqué par défaut sur
 * l'endpoint existant pour ne rien casser côté frontend — voir le
 * nouvel endpoint paginé qui l'utilise (B3.2).
 */
class DocumentRequestListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'reference' => $this->reference,
            'type'      => $this->type,
            'status'    => $this->status,
            'has_flag'  => (bool) $this->has_flag,
            'email'     => $this->email,
            'demandeur_whatsapp' => $this->demandeur_whatsapp,

            // Fichiers bruts (déjà JSON dans la colonne)
            'files'             => $this->files,
            'complement_files'  => $this->complement_files,
            'secretary_files'   => $this->secretary_files,
            'complement_at'     => $this->complement_at,

            // Étudiant
            'last_name'      => $this->last_name,
            'first_names'    => $this->first_names,
            'matricule'      => $this->matricule,
            'department'     => $this->department,
            'department_name'=> $this->department_name,
            'academic_year'  => $this->academic_year,

            // Workflow / circuit de correction
            'is_in_correction_circuit' => (bool) $this->is_in_correction_circuit,
            'correction_origin_role'   => $this->correction_origin_role,
            'correction_origin_status' => $this->correction_origin_status,
            'responsable_division_type'=> $this->responsable_division_type,
            'rejected_reason'          => $this->rejected_reason,
            'rejected_by'              => $this->rejected_by,
            'signature_type'           => $this->signature_type,

            // Commentaires (sous-requêtes historiques)
            'secretaire_comment'           => $this->secretaire_comment,
            'comptable_comment'            => $this->comptable_comment,
            'responsable_division_comment' => $this->responsable_division_comment,

            // Horodatages par acteur (sous-requêtes historiques)
            'comptable_reviewed_at'            => $this->comptable_reviewed_at,
            'responsable_division_reviewed_at' => $this->responsable_division_reviewed_at,
            'chef_cap_reviewed_at'              => $this->chef_cap_reviewed_at,
            'sec_da_reviewed_at'                => $this->sec_da_reviewed_at,
            'directrice_adjointe_reviewed_at'   => $this->directrice_adjointe_reviewed_at,
            'sec_directeur_reviewed_at'         => $this->sec_directeur_reviewed_at,

            // Horodatages
            'submitted_at' => $this->submitted_at,
            'delivered_at' => $this->delivered_at,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}

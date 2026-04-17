<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * Modèle principal du workflow de demandes de documents.
 *
 * Colonnes supprimées (redondantes avec document_request_histories) :
 *   - commentaires par rôle (× 7)
 *   - horodatages de révision par rôle (× 8)
 *   - processed_by_* (× 4)
 *   - colonnes mortes : complement_message, complement_pieces_requises,
 *                       complement_requested_at, unavailable_reason
 *
 * Ces données sont lues via document_request_histories avec des sous-requêtes
 * indexées dans DocumentRequestQueryService.
 */


class DocumentRequest extends Model
{
    protected $table = 'document_requests';

    protected $fillable = [

        // Workflow


        'status',
        'has_flag',
        'rejected_reason',
        'rejected_by',

        'signature_type',
        'chef_division_type',

        // Circuit de correction
        'is_in_correction_circuit',
        'correction_origin_role',
        'correction_origin_status',

        // Complément de dossier (actif — utilisé par ComplementDossierController)
        'complement_files',
        'complement_at',

        // Livraison
        'delivered_at',
    ];

    protected $casts = [
        'has_flag'                 => 'boolean',
        'is_in_correction_circuit' => 'boolean',
        'submitted_at'             => 'datetime',
        'delivered_at'             => 'datetime',
        'complement_at'            => 'datetime',
        'complement_files'         => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────


        'chef_division_comment',
        'secretaire_comment',
        'comptable_comment',
        'signature_type',
        'chef_division_type',
        'chef_division_reviewed_at',
        'comptable_reviewed_at',
        'chef_cap_reviewed_at',
        'sec_da_reviewed_at',
        'directrice_adjointe_reviewed_at',
        'sec_directeur_reviewed_at',
        'directeur_reviewed_at',
        'delivered_at',
        'processed_by_secretaire_id',
        'processed_by_comptable_id',
        'processed_by_chef_division_id',
        'processed_by_chef_cap_id',
        // ── Complément de dossier ──────────────────────────────────────────
        'complement_files',
        'complement_at',
        'complement_pieces_requises',
    ];

    protected $casts = [
        'has_flag'                   => 'boolean',
        'submitted_at'               => 'datetime',
        'delivered_at'               => 'datetime',
        'complement_at'              => 'datetime',
        'complement_files'           => 'array',
        'complement_pieces_requises' => 'array',
    ];

 
    public function histories(): HasMany
    {
        return $this->hasMany(DocumentRequestHistory::class)->orderBy('created_at');
    }
}

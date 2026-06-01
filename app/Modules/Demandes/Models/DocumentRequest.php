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

        // Fichiers de la secrétaire
        'secretary_files',

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
        'secretary_files'          => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function histories(): HasMany
    {
        return $this->hasMany(DocumentRequestHistory::class)->orderBy('created_at');
    }
}

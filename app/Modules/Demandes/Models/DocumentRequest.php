<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DocumentRequest — modèle Eloquent unifié.
 *
 * CORRECTIF : l'ancien fichier contenait DEUX déclarations de $fillable et
 * $casts, la seconde écrasant silencieusement la première. Résultat :
 *   - secretary_files n'était PAS dans $fillable → 500 sur upload secrétaire
 *   - is_in_correction_circuit n'était PAS casté → circuit de correction HS
 *
 * Ce fichier fusionne les deux blocs en UN SEUL, avec toutes les colonnes.
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
        'responsable_division_type',

        // Circuit de correction
        'is_in_correction_circuit',
        'correction_origin_role',
        'correction_origin_status',

        // Commentaires acteurs
        'chef_division_comment',
        'secretaire_comment',
        'comptable_comment',
        'chef_division_type',

        // Timestamps de revue par acteur
        'chef_division_reviewed_at',
        'comptable_reviewed_at',
        'chef_cap_reviewed_at',
        'sec_da_reviewed_at',
        'directrice_adjointe_reviewed_at',
        'sec_directeur_reviewed_at',
        'directeur_reviewed_at',

        // Traçabilité (qui a traité)
        'processed_by_secretaire_id',
        'processed_by_comptable_id',
        'processed_by_chef_division_id',
        'processed_by_chef_cap_id',

        // Complément de dossier
        'complement_files',
        'complement_at',
        'complement_pieces_requises',

        // Fichiers de la secrétaire
        'secretary_files',

        // Livraison
        'delivered_at',
    ];

    protected $casts = [
        'has_flag'                   => 'boolean',
        'is_in_correction_circuit'   => 'boolean',
        'submitted_at'               => 'datetime',
        'delivered_at'               => 'datetime',
        'complement_at'              => 'datetime',
        'complement_files'           => 'array',
        'complement_pieces_requises' => 'array',
        'secretary_files'            => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function histories(): HasMany
    {
        return $this->hasMany(DocumentRequestHistory::class)->orderBy('created_at');
    }

    // ── Scopes (additifs — n'affectent aucun comportement existant) ───────────

    /**
     * Demandes actives (ni rejetées, ni remises).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'picked_up']);
    }
}

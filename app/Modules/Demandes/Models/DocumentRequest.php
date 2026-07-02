<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CORRECTIF (v2) — basé sur le modèle DocumentRequest réel.
 *
 * Conservé strictement à l'identique (fillable, casts, relation histories).
 * AUCUNE colonne ajoutée/retirée : le modèle réel ne déclare PAS 'files'
 * dans $fillable ni $casts (les écritures sur 'files' passent uniquement
 * par DB::table()->insertGetId(), jamais par le modèle Eloquent) — donc
 * je n'ajoute pas de cast 'files' => 'array' ici, contrairement à ma v1
 * précédente qui l'avait fait à tort sans connaître le vrai modèle.
 *
 * AJOUT (B2.1) : scopes Eloquent utilisés uniquement par les NOUVEAUX
 * services (aucun code existant n'est impacté par leur ajout, ce sont
 * des méthodes additives qui ne changent aucun comportement actuel).
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

    // ── AJOUT (B2.1) — Scopes utilisés par les services additionnels ──────────
    // N'affectent aucun comportement existant : purement additifs.

    /**
     * Demandes actives (ni rejetées, ni remises) — reproduit la constante
     * INACTIVE_STATUSES trouvée dans DemandeController et ComplementDossierController.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'picked_up']);
    }
}

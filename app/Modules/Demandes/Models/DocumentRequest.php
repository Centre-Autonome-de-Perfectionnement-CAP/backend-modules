<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRequest extends Model
{
    protected $table = 'document_requests';

    protected $fillable = [
        'status',
        'has_flag',
        'rejected_reason',
        'rejected_by',
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
        // Complément de dossier
        'complement_files',
        'complement_at',
        'complement_pieces_requises',
        // Circuit de correction
        'is_in_correction_circuit',   // bool  — true = boucle active
        'correction_origin_role',     // string — slug du rôle ayant déclenché le rejet
        'correction_origin_status',   // string — statut BD avant le rejet (ex: 'comptable_review')
    ];

    protected $casts = [
        'has_flag'                   => 'boolean',
        'is_in_correction_circuit'   => 'boolean',
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
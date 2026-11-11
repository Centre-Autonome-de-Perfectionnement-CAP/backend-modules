<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Attestation de travail (voir WorkAttestationService). */
class StageWorkAttestation extends Model
{
    protected $table = 'stage_work_attestations';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'fonctions'     => 'array',
            'taches'        => 'array',
            'content'       => 'array',
            'date_debut'    => 'date',
            'date_fin'      => 'date',
            'document_date' => 'date',
            'delivered_on'  => 'date',
            'expires_at'    => 'datetime',
            'last_sent_at'  => 'datetime',
            'submitted_at'  => 'datetime',
            'validated_at'  => 'datetime',
            'printed_at'    => 'datetime',
            'notified_at'   => 'datetime',
            'cancelled_at'  => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(StageRequest::class, 'stage_request_id');
    }

    public function fullName(): string
    {
        return trim(mb_strtoupper((string) $this->nom) . ' ' . $this->prenoms);
    }
}

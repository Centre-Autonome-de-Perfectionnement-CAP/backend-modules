<?php

namespace App\Modules\Stages\Models;

use App\Modules\Stages\Support\StudyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StageRequest extends Model
{
    protected $table = 'stage_requests';

    protected $guarded = ['id'];

    /** Libellé de formation tenu à jour depuis la filière et le niveau. */
    protected static function booted(): void
    {
        static::saving(function (self $req) {
            if ($req->cycle && $req->isDirty(['filiere', 'cycle', 'annee_etude'])) {
                $req->filiere_niveau = StudyLevel::formation($req->filiere, $req->cycle, $req->annee_etude) ?: (string) $req->filiere;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date_naissance'        => 'date',
            'date_debut_souhaitee'  => 'date',
            'date_debut'            => 'date',
            'date_fin'              => 'date',
            'files'                 => 'array',
            'horaires'              => 'array',
            'consent_at'            => 'datetime',
            'proposal_sent_at'      => 'datetime',
            'proposal_reminded_at'  => 'datetime',
            'accepted_at'           => 'datetime',
            'rejected_at'           => 'datetime',
            'expired_at'            => 'datetime',
            'date_debut_reelle'     => 'date',
            'attendance_from'       => 'date',
            'wa_added_at'           => 'datetime',
            'wa_invite_sent_at'     => 'datetime',
            'wa_removed_at'         => 'datetime',
            'activation_sent_at'    => 'datetime',
            'activated_at'          => 'datetime',
            'withdrawn_at'          => 'datetime',
            'attestation_prepared_at' => 'datetime',
            'annee_etude'           => 'integer',
        ];
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(StageProposal::class)->orderBy('id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StageNote::class)->orderBy('version');
    }

    public function history(): HasMany
    {
        return $this->hasMany(StageHistory::class)->orderBy('id');
    }

    public function fullName(): string
    {
        return trim(mb_strtoupper($this->nom) . ' ' . $this->prenoms);
    }
}

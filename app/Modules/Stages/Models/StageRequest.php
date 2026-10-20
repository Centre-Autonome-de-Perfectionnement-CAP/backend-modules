<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StageRequest extends Model
{
    protected $table = 'stage_requests';

    protected $guarded = ['id'];

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

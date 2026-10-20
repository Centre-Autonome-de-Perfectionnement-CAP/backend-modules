<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Compte de l'espace stagiaire (distinct des comptes du personnel). */
class StageInternAccount extends Model
{
    protected $table = 'stage_intern_accounts';

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password'              => 'hashed',
            'reglement_accepted_at' => 'datetime',
            'last_login_at'         => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(StageRequest::class, 'stage_request_id');
    }
}

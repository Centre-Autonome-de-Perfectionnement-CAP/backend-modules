<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Demande du stagiaire : permission, prolongation ou raccourcissement. */
class StageInternRequest extends Model
{
    protected $table = 'stage_intern_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_from'    => 'date',
            'date_to'      => 'date',
            'new_date_fin' => 'date',
            'decided_at'   => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(StageRequest::class, 'stage_request_id');
    }
}

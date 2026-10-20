<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;

class StageProposal extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stage_request_proposals';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin'   => 'date',
            'horaires'   => 'array',
            'created_at' => 'datetime',
        ];
    }
}

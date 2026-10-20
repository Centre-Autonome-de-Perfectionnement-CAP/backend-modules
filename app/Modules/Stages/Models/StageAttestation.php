<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageAttestation extends Model
{
    protected $table = 'stage_attestations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'overrides'    => 'array',
            'content'      => 'array',
            'eligibility'  => 'array',
            'validated_at' => 'datetime',
            'printed_at'   => 'datetime',
            'delivered_on' => 'date',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminded_at'  => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(StageRequest::class, 'stage_request_id');
    }
}

<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StageReport extends Model
{
    protected $table = 'stage_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end'   => 'date',
            'late'         => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(StageRequest::class, 'stage_request_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StageReportVersion::class, 'report_id')->orderBy('version');
    }
}

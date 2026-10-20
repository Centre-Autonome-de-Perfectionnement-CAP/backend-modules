<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;

/** Version déposée d'un rapport (jamais modifiée ni supprimée). */
class StageReportVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stage_report_versions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['late' => 'boolean', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}

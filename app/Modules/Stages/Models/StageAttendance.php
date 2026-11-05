<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pointage (jamais modifié ni supprimé par l'application ; seule exception :
 * une sortie anticipée pointée à la place du départ en pause, requalifiée
 * et tracée dans l'historique, voir AttendanceService).
 */
class StageAttendance extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stage_attendances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'day'              => 'date',
            'at'               => 'datetime',
            'early'            => 'boolean',
            'position_checked' => 'boolean',
            'created_at'       => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}

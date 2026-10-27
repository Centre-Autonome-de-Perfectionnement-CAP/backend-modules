<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;

/** Historique non modifiable : aucune mise à jour ni suppression applicative. */
class StageHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stage_request_history';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}

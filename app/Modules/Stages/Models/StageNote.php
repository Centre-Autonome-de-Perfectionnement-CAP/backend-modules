<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;

class StageNote extends Model
{
    public $timestamps = false;

    protected $table = 'stage_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'snapshot'  => 'array',
            'issued_at' => 'datetime',
        ];
    }
}

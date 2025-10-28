<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Department extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'cycle_id',
        'abbreviation',
        'next_level_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date_limite' => 'date',
    ];

    /**
     * Relation avec les groupes de classe
     */
    public function classGroups()
    {
        return $this->hasMany(\App\Modules\Inscription\Models\ClassGroup::class, 'filiere_id');
    }
}

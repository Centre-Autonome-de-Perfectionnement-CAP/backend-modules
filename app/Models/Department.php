<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'filieres';

    protected $fillable = [
        'title',
        'abbreviation',
        'cycle',
        'department_id',
        'date_limite',
        'image',
        'badge',
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

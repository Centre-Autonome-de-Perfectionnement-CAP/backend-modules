<?php

namespace App\Modules\RH\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
    ];

    /**
     * Relation avec les professeurs
     */
    public function professors()
    {
        return $this->hasMany(Professor::class);
    }
}

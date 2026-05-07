<?php

namespace App\Modules\RH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use SoftDeletes;

    protected $table = 'academic_years'; // Nom de la table
    protected $primaryKey = 'id'; // Clé primaire
    protected $fillable = [
        'uuid',
        'academic_year',
        'libelle',
        'year_start',
        'year_end',
        'submission_start',
        'submission_end',
        'reclamation_start',
        'reclamation_end',
        'is_current',
    ]; // Champs remplissables
}
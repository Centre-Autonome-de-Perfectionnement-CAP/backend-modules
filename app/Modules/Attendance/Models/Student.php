<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'first_name',
        'last_name',
        'matricule',
        'phone',
        'niveau',
        'filiere_id',
        'academic_year_id',
        'fingerprint_status',
        'fingerprint_index',   // slot capteur AS608 (1-127, unique, nullable)
    ];

    protected $casts = [
        'fingerprint_status' => 'boolean',
        'fingerprint_index'  => 'integer',
    ];
}

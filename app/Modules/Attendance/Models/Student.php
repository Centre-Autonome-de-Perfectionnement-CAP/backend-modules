<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'student_id_number',
        'fingerprint_status'
    ];
}
<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'student_id',
        'course_id',
        'status',
        'room'
    ];
}
<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'student_id',
        'course_element_id',
        'room_id',
        'status',
        'on_time',
        'date',
    ];

    protected $casts = [
        'on_time' => 'boolean',
        'date'    => 'date',
    ];
}

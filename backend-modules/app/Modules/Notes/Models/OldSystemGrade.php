<?php

namespace App\Modules\Notes\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldSystemGrade extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'old_system_grades';

    protected $fillable = [
        'student_pending_student_id',
        'program_id',
        'grades',
        'average',
    ];

    protected $casts = [
        'grades' => 'array',
        'average' => 'float',
    ];

    /**
     * Accessor for grades to ensure it returns an array
     */
    public function getGradesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Relation with student
     */
    public function studentPendingStudent()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\StudentPendingStudent::class);
    }

    /**
     * Relation with program (course schedule)
     */
    public function program()
    {
        return $this->belongsTo(\App\Modules\Cours\Models\Program::class);
    }
}

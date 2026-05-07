<?php

namespace App\Modules\Notes\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LmdSystemGrade extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'lmd_system_grades';

    protected $fillable = [
        'student_pending_student_id',
        'program_id',
        'grades',
        'average',
        'retake_grades',
        'retake_average',
        'validated',
        'retaken',
        'must_retake',
    ];

    protected $casts = [
        'grades' => 'array',
        'retake_grades' => 'array',
        'average' => 'float',
        'retake_average' => 'float',
        'validated' => 'boolean',
        'retaken' => 'boolean',
        'must_retake' => 'boolean',
    ];

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

    /**
     * Check if grade is passing (>= 10/20)
     */
    public function isPassing(): bool
    {
        $finalAverage = $this->retake_average ?? $this->average;
        return $finalAverage >= 10;
    }

    /**
     * Check if retake is needed
     */
    public function needsRetake(): bool
    {
        return $this->average < 10 && $this->average >= 7;
    }

    /**
     * Check if must retake the entire course
     */
    public function mustRetakeCourse(): bool
    {
        return $this->average < 7;
    }
}

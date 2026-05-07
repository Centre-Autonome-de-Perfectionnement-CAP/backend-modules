<?php

namespace App\Modules\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasUuid;

class StudentCourseRetake extends Model
{
    use SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'student_pending_student_id',
        'program_id',
        'original_academic_year_id',
        'retake_academic_year_id',
        'original_study_level',
        'current_study_level',
        'status',
        'final_grade'
    ];

    protected $casts = [
        'final_grade' => 'decimal:2'
    ];

    public function studentPendingStudent()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\StudentPendingStudent::class);
    }

    public function program()
    {
        return $this->belongsTo(\App\Modules\Cours\Models\Program::class);
    }

    public function originalAcademicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class, 'original_academic_year_id');
    }

    public function retakeAcademicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class, 'retake_academic_year_id');
    }
}

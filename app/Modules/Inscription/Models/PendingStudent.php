<?php

namespace App\Modules\Inscription\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingStudent extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'personal_information_id',
        'tracking_code',
        'cuca_opinion',
        'cuca_comment',
        'department_id',
        'academic_year_id',
        'level',
        'documents',
        'entry_diploma_id',
        'sponsorise',
        'status',
    ];

    protected $casts = [
        'documents' => 'array',
    ];


    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function entryDiploma()
    {
        return $this->belongsTo(EntryDiploma::class);
    }

    public function studentPendingStudents()
{
    return $this->hasMany(StudentPendingStudent::class, 'pending_student_id');
}

    /**
     * Relation vers les parcours académiques via StudentPendingStudent
     */
    public function academicPaths()
    {
        return $this->hasManyThrough(
            AcademicPath::class,
            StudentPendingStudent::class,
            'pending_student_id',          // FK sur student_pending_students
            'student_pending_student_id',  // FK sur academic_paths
            'id',                          // PK locale sur pending_students
            'id'                           // PK locale sur student_pending_students
        );
    }

    /**
     * Get the files associated with this pending student.
     */
    public function files()
    {
        return $this->hasMany(\App\Modules\Stockage\Models\File::class, 'module_resource_id')
            ->where('module_name', 'inscription')
            ->where('module_resource_type', 'pending_student');
    }
}

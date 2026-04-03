<?php

namespace App\Modules\Soutenance\Models;

use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Student;
use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\RH\Models\Professor;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DefenseSubmission extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'last_name', 
        'first_names', 
        'email', 
        'contacts',
        'department_id',
        'student_id_number',
        'defense_submission_period_id',
        'thesis_title',
        'professor_id',
        'files',
        'status',
        'defense_type',
        'rejection_reason',
        'room_id',
        'defense_date',
    ];

    protected $casts = [
        'files' => 'array',
        'submission_date' => 'datetime',
        'defense_date' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id_number', 'student_id_number');
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function period()
    {
        return $this->belongsTo(DefenseSubmissionPeriod::class, 'defense_submission_period_id');
    }

    public function juryMembers()
    {
        return $this->hasMany(DefenseJuryMember::class);
    }

    public function getThesisFileUrlAttribute()
    {
        return Storage::url($this->files['thesis'] ?? '');
    }

    public function getAbstractFileUrlAttribute()
    {
        return Storage::url($this->files['abstract'] ?? '');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->whereHas('period', function($q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId);
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('defense_type', $type);
    }
}

<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\HasUuid;

class Student extends Authenticatable
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\StudentFactory::new();
    }

    protected $fillable = ['student_id_number', 'password','fingerprint_status'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Relations vers les dossiers (pending_students) via la table pivot
     */
    public function studentPendingStudents()
    {
        return $this->hasMany(StudentPendingStudent::class);
    }

    /**
     * Relation many-to-many vers PendingStudent
     */
    public function pendingStudents()
    {
        return $this->belongsToMany(PendingStudent::class, 'student_pending_student')
            ->withTimestamps();
    }

    /**
     * Récupérer les informations personnelles via le premier dossier
     */
    public function getPersonalInformationAttribute()
    {
        if (!$this->relationLoaded('pendingStudents')) {
            $this->load('pendingStudents.personalInformation');
        }
        
        return $this->pendingStudents->first()?->personalInformation;
    }

    /**
     * Parcours académiques
     */
    public function academicPaths()
    {
        return $this->hasManyThrough(
            AcademicPath::class,
            StudentPendingStudent::class,
            'student_id',
            'student_pending_student_id',
            'id',
            'id'
        );
    }

    /**
     *  Relation avec les présences
     */
    public function attendances()
    {
        return $this->hasMany(\App\Modules\Attendance\Models\Attendance::class);
    }
}
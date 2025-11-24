<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class ClassGroup extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ClassGroupFactory::new();
    }

    protected $fillable = [
        'academic_year_id',
        'department_id',
        'study_level',
        'semester1_credits', 'semester2_credits',
        'group_name',
    ];

    /**
     * Relation avec l'année académique
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relation avec la filière (Department)
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Relation avec le cycle via le département
     */
    public function cycle()
    {
        return $this->hasOneThrough(
            Cycle::class,
            Department::class,
            'id',
            'id',
            'department_id',
            'cycle_id'
        );
    }

    /**
     * Relation avec les programmes de cours
     */
    public function coursePrograms()
    {
        return $this->hasMany(\App\Modules\Cours\Models\Program::class);
    }

    /**
     * Alias pour coursePrograms
     */
    public function programs()
    {
        return $this->coursePrograms();
    }

    /**
     * Relation avec les groupes d'étudiants
     */
    public function studentGroups()
    {
        return $this->hasMany(StudentGroup::class, 'class_group_id');
    }
}

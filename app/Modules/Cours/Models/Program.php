<?php

namespace App\Modules\Cours\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'programs';

    protected $fillable = [
        'class_group_id',
        'course_element_professor_id',
        'academic_year_id',
        'weighting',
        'retake_weighting',
    ];

    protected $casts = [
        'weighting' => 'array',
        'retake_weighting' => 'array',
    ];

    /**
     * Relation avec le groupe de classe
     */
    public function classGroup()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\ClassGroup::class);
    }

    /**
     * Relation avec l'année académique
     */
    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class);
    }

    /**
     * Relation avec l'assignation cours-professeur (table pivot)
     */
    public function courseElementProfessor()
    {
        return $this->belongsTo(CourseElementProfessor::class, 'course_element_professor_id');
    }

    /**
     * Relation avec l'élément de cours via la table pivot
     */
    public function courseElement()
    {
        return $this->hasOneThrough(
            CourseElement::class,
            CourseElementProfessor::class,
            'id', // Foreign key on course_element_professor table
            'id', // Foreign key on course_elements table
            'course_element_professor_id', // Local key on programs table
            'course_element_id' // Local key on course_element_professor table
        );
    }

    /**
     * Relation avec le professeur assigné via la table pivot
     */
    public function professor()
    {
        return $this->hasOneThrough(
            \App\Modules\RH\Models\Professor::class,
            CourseElementProfessor::class,
            'id', // Foreign key on course_element_professor table
            'id', // Foreign key on professors table
            'course_element_professor_id', // Local key on programs table
            'professor_id' // Local key on course_element_professor table
        );
    }

    /**
     * Relation avec l'unité d'enseignement
     * Via courseElement -> teachingUnit
     */
    public function teachingUnit()
    {
        // Cette relation nécessite une requête personnalisée
        // car elle passe par deux tables intermédiaires
        return $this->courseElement()->with('teachingUnit');
    }
}

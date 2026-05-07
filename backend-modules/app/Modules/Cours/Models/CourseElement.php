<?php

namespace App\Modules\Cours\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseElement extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CourseElementFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
        'credits',
        'teaching_unit_id',
    ];

    protected $casts = [
        'credits' => 'integer',
    ];

    /**
     * Relation avec l'unité d'enseignement
     */
    public function teachingUnit()
    {
        return $this->belongsTo(TeachingUnit::class);
    }

    /**
     * Relation many-to-many avec les professeurs
     */
    public function professors()
    {
        return $this->belongsToMany(
            \App\Modules\RH\Models\Professor::class,
            'course_element_professor',
            'course_element_id',
            'professor_id'
        )->withTimestamps();
    }

    /**
     * Relation avec les assignations cours-professeur (table pivot avec ID)
     */
    public function courseElementProfessors()
    {
        return $this->hasMany(CourseElementProfessor::class);
    }

    /**
     * Relation avec les programmes via la table pivot
     */
    public function programs()
    {
        return $this->hasManyThrough(
            Program::class,
            CourseElementProfessor::class,
            'course_element_id', // Foreign key on course_element_professor table
            'course_element_professor_id', // Foreign key on programs table
            'id', // Local key on course_elements table
            'id' // Local key on course_element_professor table
        );
    }

    /**
     * Relation avec les ressources pédagogiques
     */
    public function resources()
    {
        return $this->hasMany(CourseElementResource::class);
    }
}

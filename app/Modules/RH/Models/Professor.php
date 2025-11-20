<?php

namespace App\Modules\RH\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Professor extends Authenticatable
{
    use HasFactory, Notifiable, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ProfessorFactory::new();
    }

    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'phone',
        'password',
        'role_id',
        'rib_number',
        'rib',
        'ifu_number',
        'ifu',
        'bank',
        'status',
        'grade_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected $appends = ['full_name'];

    /**
     * Relation avec le grade
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Relation many-to-many avec les éléments de cours
     */
    public function courseElements()
    {
        return $this->belongsToMany(
            \App\Modules\Cours\Models\CourseElement::class,
            'course_element_professor',
            'professor_id',
            'course_element_id'
        )->withTimestamps();
    }

    /**
     * Relation avec les assignations cours-professeur (table pivot avec ID)
     */
    public function courseElementProfessors()
    {
        return $this->hasMany(\App\Modules\Cours\Models\CourseElementProfessor::class);
    }

    /**
     * Relation avec les programmes via la table pivot
     */
    public function programs()
    {
        return $this->hasManyThrough(
            \App\Modules\Cours\Models\Program::class,
            \App\Modules\Cours\Models\CourseElementProfessor::class,
            'professor_id', // Foreign key on course_element_professor table
            'course_element_professor_id', // Foreign key on programs table
            'id', // Local key on professors table
            'id' // Local key on course_element_professor table
        );
    }

    /**
     * Scope pour les professeurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Obtenir le nom complet
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

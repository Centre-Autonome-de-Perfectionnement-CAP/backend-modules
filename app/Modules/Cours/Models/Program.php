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
        'weighting',
    ];

    protected $casts = [
        'weighting' => 'array',
    ];

    /**
     * Relation avec le groupe de classe
     */
    public function classGroup()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\ClassGroup::class);
    }

    /**
     * Relation avec l'élément de cours
     * Note: course_element_professor_id fait référence à l'ID de la table pivot
     */
    public function courseElement()
    {
        return $this->belongsTo(CourseElement::class, 'course_element_professor_id');
    }

    /**
     * Obtenir le professeur assigné via la table pivot
     * Cette méthode devra être personnalisée selon vos besoins exacts
     */
    public function professor()
    {
        return $this->belongsTo(Professor::class, 'course_element_professor_id');
    }
}

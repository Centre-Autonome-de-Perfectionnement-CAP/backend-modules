<?php

namespace App\Modules\Cours\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Stockage\Models\File;

class CourseElementResource extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'course_element_id',
        'file_id',
        'title',
        'description',
        'resource_type',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Relation avec l'élément de cours
     */
    public function courseElement()
    {
        return $this->belongsTo(CourseElement::class);
    }

    /**
     * Relation avec le fichier
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Scope pour les ressources publiques
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope par type de ressource
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }
}

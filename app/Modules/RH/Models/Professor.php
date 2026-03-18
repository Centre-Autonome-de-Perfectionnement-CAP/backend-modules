<?php

namespace App\Modules\RH\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Professor extends Authenticatable
{
    use HasFactory, Notifiable, HasUuid, HasApiTokens, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ProfessorFactory::new();
    }

    protected $fillable = [
        // Existants
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
        'statut',
        'grade_id',
        'specialty',
        'bio',

        // Ajoutés pour le contrat
        'nationality',
        'profession',
        'city',
        'district',
        'plot_number',
        'house_number',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected $appends = ['full_name'];

    // ─────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────

    /**
     * Relation avec le grade
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Relation avec les contrats
     */
    public function contrats()
    {
        return $this->hasMany(\App\Modules\RH\Models\Contrat::class);
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
     * Relation avec les assignations cours-professeur
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
            'professor_id',
            'course_element_professor_id',
            'id',
            'id'
        );
    }

    // ─────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────

    /**
     * Scope pour les professeurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    // ─────────────────────────────────────────
    // Accesseurs
    // ─────────────────────────────────────────

    /**
     * Nom complet
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Adresse complète formatée pour le contrat
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->district,
            $this->plot_number ? "Parcelle {$this->plot_number}" : null,
            $this->house_number ? "Maison {$this->house_number}" : null,
        ]);

        return implode(', ', $parts);
    }
}

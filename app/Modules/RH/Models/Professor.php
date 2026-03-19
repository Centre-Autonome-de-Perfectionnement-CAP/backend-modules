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

    protected static function newFactory()
    {
        return \Database\Factories\ProfessorFactory::new();
    }

    protected $fillable = [
        'uuid',
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
        'speciality',
        'bio',

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

    protected $appends = ['full_name'];

    // ───────────────────────── RELATIONS

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function contrats()
    {
        return $this->hasMany(\App\Modules\RH\Models\Contrat::class);
    }

    public function courseElements()
    {
        return $this->belongsToMany(
            \App\Modules\Cours\Models\CourseElement::class,
            'course_element_professor',
            'professor_id',
            'course_element_id'
        )->withTimestamps();
    }

    public function courseElementProfessors()
    {
        return $this->hasMany(\App\Modules\Cours\Models\CourseElementProfessor::class);
    }

    // ───────────────────────── SCOPES

    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }

    // ───────────────────────── ACCESSORS

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

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
<?php

namespace App\Modules\CahierTexte\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Cours\Models\Program;
use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use App\Models\User;

class TextbookEntry extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'program_id',
        'scheduled_course_id',
        'session_date',
        'start_time',
        'end_time',
        'hours_taught',
        'session_title',
        'content_covered',
        'objectives',
        'teaching_methods',
        'homework',
        'homework_due_date',
        'resources',
        'attachments',
        'students_present',
        'students_absent',
        'observations',
        'status',
        'published_at',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'homework_due_date' => 'date',
        'published_at' => 'datetime',
        'validated_at' => 'datetime',
        'hours_taught' => 'decimal:2',
        'students_present' => 'integer',
        'students_absent' => 'integer',
        'resources' => 'array',
        'attachments' => 'array',
    ];

    /**
     * Relation avec le programme de cours
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relation avec le cours planifié
     */
    public function scheduledCourse()
    {
        return $this->belongsTo(ScheduledCourse::class);
    }

    /**
     * Relation avec le validateur
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Relation avec les commentaires
     */
    public function comments()
    {
        return $this->hasMany(TextbookComment::class);
    }

    /**
     * Récupérer l'élément de cours via le programme
     */
    public function courseElement()
    {
        return $this->program->courseElement();
    }

    /**
     * Récupérer le professeur via le programme
     */
    public function professor()
    {
        return $this->program->professor();
    }

    /**
     * Récupérer le groupe de classe via le programme
     */
    public function classGroup()
    {
        return $this->program->classGroup();
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer par programme
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_date', [$startDate, $endDate]);
    }

    /**
     * Scope pour filtrer par groupe de classe
     */
    public function scopeByClassGroup($query, $classGroupId)
    {
        return $query->whereHas('program.classGroup', function ($q) use ($classGroupId) {
            $q->where('id', $classGroupId);
        });
    }

    /**
     * Scope pour filtrer par professeur
     */
    public function scopeByProfessor($query, $professorId)
    {
        return $query->whereHas('program.professor', function ($q) use ($professorId) {
            $q->where('id', $professorId);
        });
    }

    /**
     * Publier l'entrée
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Valider l'entrée
     */
    public function validate(User $validator)
    {
        $this->update([
            'status' => 'validated',
            'validated_by' => $validator->id,
            'validated_at' => now(),
        ]);
    }

    /**
     * Vérifier si l'entrée est publiée
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' || $this->status === 'validated';
    }

    /**
     * Vérifier si l'entrée est validée
     */
    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }
}

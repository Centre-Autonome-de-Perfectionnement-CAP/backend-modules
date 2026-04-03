<?php

namespace App\Modules\RH\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrat extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'contrats';

    protected $fillable = [
        'uuid',
        'contrat_number',
        'division',
        'professor_id',
        'academic_year_id',
        'cycle_id',
        'regroupement',
        'start_date',
        'end_date',
        'amount',
        'validation_date',
        'is_validated',
        'is_authorized',
        'authorization_date',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'validation_date' => 'date',
        'authorization_date'=> 'datetime',
        'is_validated'    => 'boolean',
        'amount'          => 'decimal:2',
        'is_authorized'     => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    public function cycle(){
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Programmes rattachés à ce contrat
     * (chaque programme = Professeur + Matière ECUE + Classe)
     */
    public function courseElementProfessors()
    {
        return $this->belongsToMany(
            \App\Modules\Cours\Models\CourseElementProfessor::class,
            'contrat_programs',
            'contrat_id',
            'course_element_professor_id'
        )->with(['courseElement.teachingUnit', 'classGroup']);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'En attente',
            'signed'    => 'Signé',
            'ongoing'   => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Résilié',
            'rejected'  => 'Rejeté', 
            default     => 'Inconnu',
        };
    }
}

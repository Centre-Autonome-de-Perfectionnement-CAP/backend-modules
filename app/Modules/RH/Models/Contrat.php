<?php

namespace App\Modules\RH\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        // ── Signature électronique ──────────────────────────────────────────
        'professor_signature_path',
        'professor_signature_type',  // 'drawn' | 'uploaded'
        'professor_signed_at',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'end_date'            => 'date',
        'validation_date'     => 'date',
        'authorization_date'  => 'datetime',
        'professor_signed_at' => 'datetime',
        'is_validated'        => 'boolean',
        'is_authorized'       => 'boolean',
        'amount'              => 'decimal:2',
    ];

    // ─── Appends ──────────────────────────────────────────────────────────────

    protected $appends = ['professor_signature_url'];

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * URL publique de la signature (null si aucune signature)
     */
    public function getProfessorSignatureUrlAttribute(): ?string
    {
        if (!$this->professor_signature_path) {
            return null;
        }
        return Storage::disk('public')->url($this->professor_signature_path);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'En attente',
            'transfered' => 'Transféré',
            'signed'     => 'Signé',
            'ongoing'    => 'En cours',
            'completed'  => 'Terminé',
            'cancelled'  => 'Rejeté',
            default      => 'Inconnu',
        };
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    public function cycle()
    {
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
}

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
        'start_date',
        'end_date',
        'amount',
        'validation_date',
        'is_validated',
        'statut',
        'notes',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'validation_date' => 'date',
        'is_validated'    => 'boolean',
        'amount'          => 'decimal:2',
    ];

    // ─────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    // public function academicYear()
    // {
    //     return $this->belongsTo(\App\Modules\Academic\Models\AcademicYear::class);
    // }

    // public function courses()
    // {
    //     return $this->hasMany(ContractCourse::class);
    // }

    // public function payments()
    // {
    //     return $this->hasMany(Payment::class);
    // }

    // ─────────────────────────────────────────
    // Accesseurs
    // ─────────────────────────────────────────

    /**
     * Libellé du statut en français
     */
    public function getstatutLabelAttribute(): string
    {
        return match($this->statut) {
            'pending'   => 'En attente',
            'signed'    => 'Signé',
            'ongoing'   => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Résilié',
            default     => 'Inconnu',
        };
    }
}

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
        'status',
        'notes',
        'regroupement',
        'cycle_id',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'validation_date' => 'date',
        'is_validated'    => 'boolean',
        'amount'          => 'decimal:2',
    ];
     public function professor()
     {
         return $this->belongsTo(Professor::class, 'professor_id');
     }
public function cycle()
{
    return $this->belongsTo(Cycle::class);
}
     public function academicYear()
{
    return $this->belongsTo(AcademicYear::class, 'academic_year_id');
}

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'En attente',
            'signed'    => 'Signé',
            'ongoing'   => 'En cours',
            'completed' => 'Terminé',
            'cancelled' => 'Résilié',
            default     => 'Inconnu',
        };
    }
}

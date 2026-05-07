<?php

namespace App\Modules\EmploiDuTemps\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Cours\Models\Program;
use Carbon\Carbon;

class ScheduledCourse extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'program_id',
        'time_slot_id',
        'room_id',
        'start_date',
        'end_date',
        'total_hours',
        'hours_completed',
        'is_recurring',
        'recurrence_end_date',
        'excluded_dates',
        'notes',
        'is_cancelled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'recurrence_end_date' => 'date',
        'excluded_dates' => 'array',
        'total_hours' => 'decimal:2',
        'hours_completed' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Relation avec le programme de cours
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relation avec le créneau horaire
     */
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * Relation avec la salle
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
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
     * Calculer la date de fin estimée selon la masse horaire
     */
    public function calculateEstimatedEndDate(): ?Carbon
    {
        if (!$this->total_hours || !$this->start_date || !$this->timeSlot) {
            return null;
        }

        $hoursPerWeek = $this->timeSlot->duration_in_hours;
        if ($hoursPerWeek <= 0) {
            return null;
        }

        $weeksNeeded = ceil($this->total_hours / $hoursPerWeek);
        
        return Carbon::parse($this->start_date)->addWeeks($weeksNeeded);
    }

    /**
     * Calculer le nombre d'heures restantes
     */
    public function getRemainingHoursAttribute(): float
    {
        return max(0, $this->total_hours - $this->hours_completed);
    }

    /**
     * Calculer le pourcentage de progression
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_hours <= 0) {
            return 0;
        }

        return round(($this->hours_completed / $this->total_hours) * 100, 2);
    }

    /**
     * Vérifier si le cours est terminé
     */
    public function isCompleted(): bool
    {
        return $this->hours_completed >= $this->total_hours;
    }

    /**
     * Générer toutes les occurrences du cours récurrent
     */
    public function getOccurrences(): array
    {
        if (!$this->is_recurring) {
            return [Carbon::parse($this->start_date)];
        }

        $occurrences = [];
        $currentDate = Carbon::parse($this->start_date);
        $endDate = $this->recurrence_end_date 
            ? Carbon::parse($this->recurrence_end_date)
            : $this->calculateEstimatedEndDate();

        if (!$endDate) {
            return [Carbon::parse($this->start_date)];
        }

        while ($currentDate->lte($endDate)) {
            // Vérifier que la date n'est pas dans les dates exclues
            if (!in_array($currentDate->format('Y-m-d'), $this->excluded_dates ?? [])) {
                $occurrences[] = $currentDate->copy();
            }
            
            $currentDate->addWeek();
        }

        return $occurrences;
    }
}

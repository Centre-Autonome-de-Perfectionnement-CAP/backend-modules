<?php

namespace App\Modules\EmploiDuTemps\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeSlot extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TimeSlotFactory::new();
    }

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'type',
        'name',
    ];

    /**
     * Jours de la semaine
     */
    const DAY_MONDAY = 'monday';
    const DAY_TUESDAY = 'tuesday';
    const DAY_WEDNESDAY = 'wednesday';
    const DAY_THURSDAY = 'thursday';
    const DAY_FRIDAY = 'friday';
    const DAY_SATURDAY = 'saturday';
    const DAY_SUNDAY = 'sunday';

    /**
     * Types de créneaux
     */
    const TYPE_LECTURE = 'lecture'; // Cours magistral
    const TYPE_TD = 'td'; // Travaux dirigés
    const TYPE_TP = 'tp'; // Travaux pratiques
    const TYPE_EXAM = 'exam'; // Examen

    /**
     * Relation avec les cours planifiés
     */
    public function scheduledCourses()
    {
        return $this->hasMany(ScheduledCourse::class);
    }

    /**
     * Calculer la durée du créneau en minutes
     */
    public function getDurationInMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        
        return $start->diffInMinutes($end);
    }

    /**
     * Calculer la durée du créneau en heures
     */
    public function getDurationInHoursAttribute(): float
    {
        return $this->duration_in_minutes / 60;
    }

    /**
     * Vérifier si un créneau chevauche un autre
     */
    public function overlapsWith(TimeSlot $other): bool
    {
        if ($this->day_of_week !== $other->day_of_week) {
            return false;
        }

        $thisStart = Carbon::parse($this->start_time);
        $thisEnd = Carbon::parse($this->end_time);
        $otherStart = Carbon::parse($other->start_time);
        $otherEnd = Carbon::parse($other->end_time);

        return $thisStart->lt($otherEnd) && $otherStart->lt($thisEnd);
    }
}

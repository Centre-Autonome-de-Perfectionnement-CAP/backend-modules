<?php

namespace App\Modules\EmploiDuTemps\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Cours\Models\Program;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\ClassGroup;
use Carbon\Carbon;

class EmploiDuTemps extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'emploi_du_temps';

    protected $fillable = [
        'academic_year_id',
        'department_id',
        'class_group_id',   // ← nouveau champ direct
        'program_id',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_recurring',
        'recurrence_end_date',
        'excluded_dates',
        'notes',
        'is_cancelled',
        'is_active',
    ];

    protected $casts = [
        'recurrence_end_date' => 'date',
        'excluded_dates'      => 'array',
        'is_recurring'        => 'boolean',
        'is_cancelled'        => 'boolean',
        'is_active'           => 'boolean',
    ];

    // ──────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // ──────────────────────────────────────────
    // Accesseurs calculés (pas stockés en base)
    // ──────────────────────────────────────────

    /**
     * Durée du créneau en minutes (calculée depuis start_time / end_time)
     */
    public function getDurationInMinutesAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) return 0;
        return (int) Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time));
    }

    /**
     * Durée du créneau en heures (ex: 1.5 pour 1h30)
     */
    public function getDurationInHoursAttribute(): float
    {
        return round($this->duration_in_minutes / 60, 2);
    }

    /**
     * Durée formatée lisible (ex: "1h30")
     */
    public function getDurationFormattedAttribute(): string
    {
        $minutes = $this->duration_in_minutes;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $m > 0 ? "{$h}h{$m}" : "{$h}h";
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_cancelled', false);
    }

    public function scopeByAcademicYear($query, int $id)
    {
        return $query->where('academic_year_id', $id);
    }

    public function scopeByDepartment($query, int $id)
    {
        return $query->where('department_id', $id);
    }

    public function scopeByClassGroup($query, int $id)
    {
        return $query->where('class_group_id', $id);
    }

    public function scopeByDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function scopeByRoom($query, int $id)
    {
        return $query->where('room_id', $id);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /**
     * Vérifie si ce créneau chevauche un autre sur le même jour
     */
    public function overlapsWith(self $other): bool
    {
        if ($this->day_of_week !== $other->day_of_week) return false;

        $s1 = Carbon::parse($this->start_time);
        $e1 = Carbon::parse($this->end_time);
        $s2 = Carbon::parse($other->start_time);
        $e2 = Carbon::parse($other->end_time);

        return $s1->lt($e2) && $s2->lt($e1);
    }

    /**
     * Génère toutes les dates d'occurrence du cours (hebdomadaire)
     */
    public function getOccurrences(): array
    {
        if (!$this->is_recurring || !$this->recurrence_end_date) {
            return [];
        }

        $occurrences = [];
        // Trouver la première date correspondant au jour de la semaine
        $start   = Carbon::now()->startOfWeek();
        $end     = Carbon::parse($this->recurrence_end_date);
        $dayMap  = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7,
        ];
        $targetDay = $dayMap[$this->day_of_week] ?? 1;

        // Première occurrence : prochain jour correspondant
        $current = $start->copy()->next($targetDay);
        if ($current->gt($end)) return [];

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            if (!in_array($dateStr, $this->excluded_dates ?? [])) {
                $occurrences[] = $current->copy();
            }
            $current->addWeek();
        }

        return $occurrences;
    }
    public function getRouteKeyName()
    {
        return 'uuid'; // ou le nom réel de ta colonne UUID
    }
}
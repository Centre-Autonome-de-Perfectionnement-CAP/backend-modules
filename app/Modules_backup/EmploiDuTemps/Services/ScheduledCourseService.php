<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class ScheduledCourseService
{
    public function __construct(
        protected ConflictDetectionService $conflictDetectionService
    ) {}

    /**
     * Récupérer tous les cours planifiés avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = ScheduledCourse::query()
            ->with([
                'timeSlot',
                'room.building',
                'program.classGroup',
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor'
            ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('program.courseElementProfessor.courseElement', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (!empty($filters['time_slot_id'])) {
            $query->where('time_slot_id', $filters['time_slot_id']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['class_group_id'])) {
            $query->whereHas('program', function ($q) use ($filters) {
                $q->where('class_group_id', $filters['class_group_id']);
            });
        }

        if (!empty($filters['professor_id'])) {
            $query->whereHas('program.courseElementProfessor', function ($q) use ($filters) {
                $q->where('professor_id', $filters['professor_id']);
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('start_date', '<=', $filters['end_date']);
        }

        if (isset($filters['is_cancelled'])) {
            $query->where('is_cancelled', $filters['is_cancelled']);
        }

        if (isset($filters['is_recurring'])) {
            $query->where('is_recurring', $filters['is_recurring']);
        }

        $sortBy = $filters['sort_by'] ?? 'start_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un nouveau cours planifié
     */
    public function create(array $data): ScheduledCourse
    {
        // Vérifier les conflits
        $conflicts = $this->conflictDetectionService->detectConflicts($data);
        
        if (!empty($conflicts)) {
            throw new Exception(json_encode([
                'message' => 'Des conflits ont été détectés',
                'conflicts' => $conflicts
            ]));
        }

        DB::beginTransaction();
        try {
            // Calculer la date de fin estimée si non fournie
            if (empty($data['end_date']) && empty($data['recurrence_end_date'])) {
                $scheduledCourse = new ScheduledCourse($data);
                $estimatedEndDate = $scheduledCourse->calculateEstimatedEndDate();
                
                if ($estimatedEndDate) {
                    $data['end_date'] = $estimatedEndDate->format('Y-m-d');
                    if ($data['is_recurring'] ?? true) {
                        $data['recurrence_end_date'] = $estimatedEndDate->format('Y-m-d');
                    }
                }
            }

            $scheduledCourse = ScheduledCourse::create($data);

            Log::info('Cours planifié créé', [
                'scheduled_course_id' => $scheduledCourse->id,
                'program_id' => $scheduledCourse->program_id,
                'start_date' => $scheduledCourse->start_date->format('Y-m-d'),
            ]);

            DB::commit();
            return $scheduledCourse;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Récupérer un cours planifié par ID
     */
    public function getById(int $id): ?ScheduledCourse
    {
        return ScheduledCourse::with([
            'timeSlot',
            'room.building',
            'program.classGroup',
            'program.courseElementProfessor.courseElement',
            'program.courseElementProfessor.professor'
        ])->find($id);
    }

    /**
     * Mettre à jour un cours planifié
     */
    public function update(ScheduledCourse $scheduledCourse, array $data): ScheduledCourse
    {
        // Fusionner les données existantes avec les nouvelles
        $mergedData = array_merge([
            'program_id' => $scheduledCourse->program_id,
            'time_slot_id' => $scheduledCourse->time_slot_id,
            'room_id' => $scheduledCourse->room_id,
            'start_date' => $scheduledCourse->start_date->format('Y-m-d'),
            'is_recurring' => $scheduledCourse->is_recurring,
            'recurrence_end_date' => $scheduledCourse->recurrence_end_date?->format('Y-m-d'),
        ], $data);

        // Vérifier les conflits (en excluant le cours actuel)
        $conflicts = $this->conflictDetectionService->detectConflicts($mergedData, $scheduledCourse->id);
        
        if (!empty($conflicts)) {
            throw new Exception(json_encode([
                'message' => 'Des conflits ont été détectés',
                'conflicts' => $conflicts
            ]));
        }

        $scheduledCourse->update($data);

        Log::info('Cours planifié mis à jour', [
            'scheduled_course_id' => $scheduledCourse->id,
        ]);

        return $scheduledCourse->fresh([
            'timeSlot',
            'room.building',
            'program.classGroup',
            'program.courseElementProfessor.courseElement',
            'program.courseElementProfessor.professor'
        ]);
    }

    /**
     * Supprimer un cours planifié
     */
    public function delete(ScheduledCourse $scheduledCourse): bool
    {
        try {
            $scheduledCourse->delete();

            Log::info('Cours planifié supprimé', [
                'scheduled_course_id' => $scheduledCourse->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression du cours planifié', [
                'scheduled_course_id' => $scheduledCourse->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Annuler un cours planifié
     */
    public function cancel(ScheduledCourse $scheduledCourse, ?string $notes = null): ScheduledCourse
    {
        $scheduledCourse->update([
            'is_cancelled' => true,
            'notes' => $notes ?? $scheduledCourse->notes,
        ]);

        Log::info('Cours planifié annulé', [
            'scheduled_course_id' => $scheduledCourse->id,
        ]);

        return $scheduledCourse->fresh();
    }

    /**
     * Mettre à jour les heures effectuées
     */
    public function updateCompletedHours(ScheduledCourse $scheduledCourse, float $hoursCompleted): ScheduledCourse
    {
        $scheduledCourse->update([
            'hours_completed' => $hoursCompleted,
        ]);

        Log::info('Heures effectuées mises à jour', [
            'scheduled_course_id' => $scheduledCourse->id,
            'hours_completed' => $hoursCompleted,
            'total_hours' => $scheduledCourse->total_hours,
        ]);

        return $scheduledCourse->fresh();
    }

    /**
     * Récupérer l'emploi du temps d'un groupe de classe
     */
    public function getScheduleByClassGroup(int $classGroupId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = ScheduledCourse::with([
                'timeSlot',
                'room.building',
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor'
            ])
            ->whereHas('program', function ($q) use ($classGroupId) {
                $q->where('class_group_id', $classGroupId);
            })
            ->where('is_cancelled', false);

        if ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $q->whereDate('start_date', '<=', $startDate)
                  ->where(function ($endQ) use ($startDate) {
                      $endQ->whereNull('recurrence_end_date')
                           ->orWhereDate('recurrence_end_date', '>=', $startDate);
                  });
            });
        }

        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        return $query->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Récupérer l'emploi du temps d'un professeur
     */
    public function getScheduleByProfessor(int $professorId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = ScheduledCourse::with([
                'timeSlot',
                'room.building',
                'program.classGroup',
                'program.courseElementProfessor.courseElement'
            ])
            ->whereHas('program.courseElementProfessor', function ($q) use ($professorId) {
                $q->where('professor_id', $professorId);
            })
            ->where('is_cancelled', false);

        if ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $q->whereDate('start_date', '<=', $startDate)
                  ->where(function ($endQ) use ($startDate) {
                      $endQ->whereNull('recurrence_end_date')
                           ->orWhereDate('recurrence_end_date', '>=', $startDate);
                  });
            });
        }

        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        return $query->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Récupérer l'emploi du temps d'une salle
     */
    public function getScheduleByRoom(int $roomId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = ScheduledCourse::with([
                'timeSlot',
                'program.classGroup',
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor'
            ])
            ->where('room_id', $roomId)
            ->where('is_cancelled', false);

        if ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $q->whereDate('start_date', '<=', $startDate)
                  ->where(function ($endQ) use ($startDate) {
                      $endQ->whereNull('recurrence_end_date')
                           ->orWhereDate('recurrence_end_date', '>=', $startDate);
                  });
            });
        }

        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        return $query->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Exclure une date d'un cours récurrent
     */
    public function excludeDate(ScheduledCourse $scheduledCourse, string $date): ScheduledCourse
    {
        $excludedDates = $scheduledCourse->excluded_dates ?? [];
        
        if (!in_array($date, $excludedDates)) {
            $excludedDates[] = $date;
            
            $scheduledCourse->update([
                'excluded_dates' => $excludedDates,
            ]);

            Log::info('Date exclue du cours récurrent', [
                'scheduled_course_id' => $scheduledCourse->id,
                'excluded_date' => $date,
            ]);
        }

        return $scheduledCourse->fresh();
    }
}

<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\Cours\Models\Program;
use Carbon\Carbon;

class ConflictDetectionService
{
    /**
     * Détecter tous les conflits pour un cours planifié
     */
    public function detectConflicts(array $courseData, ?int $excludeScheduledCourseId = null): array
    {
        $conflicts = [];

        // 1. Conflit de salle
        $roomConflict = $this->checkRoomConflict(
            $courseData['room_id'],
            $courseData['time_slot_id'],
            $courseData['start_date'],
            $courseData['is_recurring'] ?? true,
            $courseData['recurrence_end_date'] ?? null,
            $excludeScheduledCourseId
        );
        if ($roomConflict) {
            $conflicts[] = $roomConflict;
        }

        // 2. Conflit de professeur
        $professorConflict = $this->checkProfessorConflict(
            $courseData['program_id'],
            $courseData['time_slot_id'],
            $courseData['start_date'],
            $courseData['is_recurring'] ?? true,
            $courseData['recurrence_end_date'] ?? null,
            $excludeScheduledCourseId
        );
        if ($professorConflict) {
            $conflicts[] = $professorConflict;
        }

        // 3. Conflit de groupe de classe (étudiants)
        $classGroupConflict = $this->checkClassGroupConflict(
            $courseData['program_id'],
            $courseData['time_slot_id'],
            $courseData['start_date'],
            $courseData['is_recurring'] ?? true,
            $courseData['recurrence_end_date'] ?? null,
            $excludeScheduledCourseId
        );
        if ($classGroupConflict) {
            $conflicts[] = $classGroupConflict;
        }

        // 4. Conflit de capacité de salle
        $capacityConflict = $this->checkRoomCapacity(
            $courseData['room_id'],
            $courseData['program_id']
        );
        if ($capacityConflict) {
            $conflicts[] = $capacityConflict;
        }

        return $conflicts;
    }

    /**
     * Vérifier les conflits de salle
     */
    protected function checkRoomConflict(
        int $roomId,
        int $timeSlotId,
        string $startDate,
        bool $isRecurring,
        ?string $recurrenceEndDate,
        ?int $excludeId = null
    ): ?array {
        $query = ScheduledCourse::where('room_id', $roomId)
            ->where('time_slot_id', $timeSlotId)
            ->where('is_cancelled', false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Vérifier les chevauchements de dates
        $query->where(function ($q) use ($startDate, $recurrenceEndDate) {
            $q->where(function ($subQ) use ($startDate, $recurrenceEndDate) {
                // Le cours existant commence avant ou pendant notre période
                $subQ->where('start_date', '<=', $recurrenceEndDate ?? $startDate);
                
                // Et se termine après ou pendant notre période (ou n'a pas de fin)
                $subQ->where(function ($endQ) use ($startDate) {
                    $endQ->whereNull('recurrence_end_date')
                         ->orWhere('recurrence_end_date', '>=', $startDate);
                });
            });
        });

        $conflictingCourse = $query->with(['program.classGroup', 'program.courseElementProfessor.courseElement'])->first();

        if ($conflictingCourse) {
            return [
                'type' => 'room',
                'message' => 'La salle est déjà occupée à ce créneau horaire',
                'conflicting_course' => [
                    'id' => $conflictingCourse->id,
                    'course_name' => $conflictingCourse->program->courseElementProfessor->courseElement->name ?? 'N/A',
                    'class_group' => $conflictingCourse->program->classGroup->group_name ?? 'N/A',
                    'start_date' => $conflictingCourse->start_date->format('Y-m-d'),
                ],
            ];
        }

        return null;
    }

    /**
     * Vérifier les conflits de professeur
     */
    protected function checkProfessorConflict(
        int $programId,
        int $timeSlotId,
        string $startDate,
        bool $isRecurring,
        ?string $recurrenceEndDate,
        ?int $excludeId = null
    ): ?array {
        // Récupérer le professeur du programme
        $program = Program::with('courseElementProfessor.professor')->find($programId);
        if (!$program || !$program->courseElementProfessor) {
            return null;
        }

        $professorId = $program->courseElementProfessor->professor_id;

        // Chercher d'autres cours du même professeur au même créneau
        $query = ScheduledCourse::whereHas('program.courseElementProfessor', function ($q) use ($professorId) {
                $q->where('professor_id', $professorId);
            })
            ->where('time_slot_id', $timeSlotId)
            ->where('is_cancelled', false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Vérifier les chevauchements de dates
        $query->where(function ($q) use ($startDate, $recurrenceEndDate) {
            $q->where(function ($subQ) use ($startDate, $recurrenceEndDate) {
                $subQ->where('start_date', '<=', $recurrenceEndDate ?? $startDate);
                $subQ->where(function ($endQ) use ($startDate) {
                    $endQ->whereNull('recurrence_end_date')
                         ->orWhere('recurrence_end_date', '>=', $startDate);
                });
            });
        });

        $conflictingCourse = $query->with(['program.classGroup', 'room'])->first();

        if ($conflictingCourse) {
            return [
                'type' => 'professor',
                'message' => 'Le professeur a déjà un cours à ce créneau horaire',
                'conflicting_course' => [
                    'id' => $conflictingCourse->id,
                    'class_group' => $conflictingCourse->program->classGroup->group_name ?? 'N/A',
                    'room' => $conflictingCourse->room->name ?? 'N/A',
                    'start_date' => $conflictingCourse->start_date->format('Y-m-d'),
                ],
            ];
        }

        return null;
    }

    /**
     * Vérifier les conflits de groupe de classe
     */
    protected function checkClassGroupConflict(
        int $programId,
        int $timeSlotId,
        string $startDate,
        bool $isRecurring,
        ?string $recurrenceEndDate,
        ?int $excludeId = null
    ): ?array {
        // Récupérer le groupe de classe du programme
        $program = Program::with('classGroup')->find($programId);
        if (!$program) {
            return null;
        }

        $classGroupId = $program->class_group_id;

        // Chercher d'autres cours du même groupe au même créneau
        $query = ScheduledCourse::whereHas('program', function ($q) use ($classGroupId) {
                $q->where('class_group_id', $classGroupId);
            })
            ->where('time_slot_id', $timeSlotId)
            ->where('is_cancelled', false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Vérifier les chevauchements de dates
        $query->where(function ($q) use ($startDate, $recurrenceEndDate) {
            $q->where(function ($subQ) use ($startDate, $recurrenceEndDate) {
                $subQ->where('start_date', '<=', $recurrenceEndDate ?? $startDate);
                $subQ->where(function ($endQ) use ($startDate) {
                    $endQ->whereNull('recurrence_end_date')
                         ->orWhere('recurrence_end_date', '>=', $startDate);
                });
            });
        });

        $conflictingCourse = $query->with(['program.courseElementProfessor.courseElement', 'room'])->first();

        if ($conflictingCourse) {
            return [
                'type' => 'class_group',
                'message' => 'Le groupe de classe a déjà un cours à ce créneau horaire',
                'conflicting_course' => [
                    'id' => $conflictingCourse->id,
                    'course_name' => $conflictingCourse->program->courseElementProfessor->courseElement->name ?? 'N/A',
                    'room' => $conflictingCourse->room->name ?? 'N/A',
                    'start_date' => $conflictingCourse->start_date->format('Y-m-d'),
                ],
            ];
        }

        return null;
    }

    /**
     * Vérifier si la capacité de la salle est suffisante
     */
    protected function checkRoomCapacity(int $roomId, int $programId): ?array
    {
        $room = Room::find($roomId);
        $program = Program::with('classGroup.studentGroups')->find($programId);

        if (!$room || !$program || !$program->classGroup) {
            return null;
        }

        // Calculer le nombre d'étudiants dans le groupe de classe
        $studentCount = $program->classGroup->studentGroups()->count();

        if ($studentCount > $room->capacity) {
            return [
                'type' => 'capacity',
                'message' => "La salle n'a pas une capacité suffisante",
                'details' => [
                    'room_capacity' => $room->capacity,
                    'student_count' => $studentCount,
                    'missing_seats' => $studentCount - $room->capacity,
                ],
            ];
        }

        return null;
    }

    /**
     * Vérifier si un cours planifié a des conflits
     */
    public function hasConflicts(ScheduledCourse $scheduledCourse): bool
    {
        $conflicts = $this->detectConflicts([
            'program_id' => $scheduledCourse->program_id,
            'time_slot_id' => $scheduledCourse->time_slot_id,
            'room_id' => $scheduledCourse->room_id,
            'start_date' => $scheduledCourse->start_date->format('Y-m-d'),
            'is_recurring' => $scheduledCourse->is_recurring,
            'recurrence_end_date' => $scheduledCourse->recurrence_end_date?->format('Y-m-d'),
        ], $scheduledCourse->id);

        return count($conflicts) > 0;
    }
}

<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use App\Modules\EmploiDuTemps\Models\TimeSlot;
use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\Cours\Models\Program;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ScheduleGeneratorService
{
    public function __construct(
        protected ConflictDetectionService $conflictDetectionService,
        protected ScheduledCourseService $scheduledCourseService
    ) {}

    /**
     * Générer automatiquement un emploi du temps pour une année académique
     */
    public function generateSchedule(int $academicYearId, array $options = []): array
    {
        DB::beginTransaction();
        try {
            // Récupérer tous les programmes de l'année
            $programs = Program::whereHas('classGroup', function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                })
                ->with([
                    'classGroup',
                    'courseElementProfessor.courseElement',
                    'courseElementProfessor.professor'
                ])
                ->get();

            if ($programs->isEmpty()) {
                throw new Exception("Aucun programme trouvé pour cette année académique");
            }

            // Récupérer les créneaux disponibles
            $timeSlots = TimeSlot::orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            if ($timeSlots->isEmpty()) {
                throw new Exception("Aucun créneau horaire configuré");
            }

            // Récupérer les salles disponibles
            $rooms = Room::where('is_available', true)
                ->with('building')
                ->get();

            if ($rooms->isEmpty()) {
                throw new Exception("Aucune salle disponible");
            }

            $startDate = $options['start_date'] ?? Carbon::now()->startOfWeek();
            if (is_string($startDate)) {
                $startDate = Carbon::parse($startDate);
            }

            $created = 0;
            $skipped = 0;
            $errors = [];

            // Trier les programmes par priorité (plus d'heures = plus prioritaire)
            $sortedPrograms = $programs->sortByDesc(function ($program) {
                return $program->courseElementProfessor->courseElement->total_hours ?? 0;
            });

            foreach ($sortedPrograms as $program) {
                try {
                    $courseElement = $program->courseElementProfessor->courseElement;
                    $totalHours = $courseElement->total_hours ?? 0;

                    if ($totalHours <= 0) {
                        $skipped++;
                        continue;
                    }

                    // Trouver le meilleur créneau et la meilleure salle
                    $assignment = $this->findBestAssignment(
                        $program,
                        $timeSlots,
                        $rooms,
                        $startDate,
                        $options
                    );

                    if (!$assignment) {
                        $skipped++;
                        $errors[] = "Impossible de trouver un créneau pour {$courseElement->name} - {$program->classGroup->group_name}";
                        continue;
                    }

                    // Créer le cours planifié
                    $scheduledCourse = ScheduledCourse::create([
                        'program_id' => $program->id,
                        'time_slot_id' => $assignment['time_slot']->id,
                        'room_id' => $assignment['room']->id,
                        'start_date' => $assignment['start_date']->format('Y-m-d'),
                        'total_hours' => $totalHours,
                        'hours_completed' => 0,
                        'is_recurring' => true,
                        'notes' => 'Généré automatiquement',
                        'is_cancelled' => false,
                    ]);

                    // Calculer et mettre à jour la date de fin
                    $estimatedEndDate = $scheduledCourse->calculateEstimatedEndDate();
                    if ($estimatedEndDate) {
                        $scheduledCourse->update([
                            'end_date' => $estimatedEndDate->format('Y-m-d'),
                            'recurrence_end_date' => $estimatedEndDate->format('Y-m-d'),
                        ]);
                    }

                    $created++;

                } catch (Exception $e) {
                    $skipped++;
                    $courseName = $courseElement->name ?? 'cours';
                    $errors[] = "Erreur pour {$courseName}: {$e->getMessage()}";
                    Log::error('Erreur lors de la génération d\'un cours', [
                        'program_id' => $program->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Génération d\'emploi du temps terminée', [
                'academic_year_id' => $academicYearId,
                'created' => $created,
                'skipped' => $skipped,
            ]);

            return [
                'success' => true,
                'created' => $created,
                'skipped' => $skipped,
                'total' => $programs->count(),
                'errors' => $errors,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la génération d\'emploi du temps', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Trouver la meilleure assignation (créneau + salle) pour un programme
     */
    protected function findBestAssignment($program, $timeSlots, $rooms, $startDate, $options = []): ?array
    {
        $courseElement = $program->courseElementProfessor->courseElement;
        $classGroup = $program->classGroup;
        $studentCount = $classGroup->studentGroups()->count();

        // Filtrer les salles par capacité
        $suitableRooms = $rooms->filter(function ($room) use ($studentCount) {
            return $room->capacity >= $studentCount;
        });

        if ($suitableRooms->isEmpty()) {
            return null;
        }

        // Essayer chaque créneau
        foreach ($timeSlots as $timeSlot) {
            // Calculer la date de début pour ce jour de la semaine
            $courseStartDate = $this->getNextDateForDay($startDate, $timeSlot->day_of_week);

            // Essayer chaque salle
            foreach ($suitableRooms as $room) {
                // Vérifier les conflits
                $conflicts = $this->conflictDetectionService->detectConflicts([
                    'program_id' => $program->id,
                    'time_slot_id' => $timeSlot->id,
                    'room_id' => $room->id,
                    'start_date' => $courseStartDate->format('Y-m-d'),
                    'is_recurring' => true,
                    'recurrence_end_date' => $courseStartDate->copy()->addMonths(4)->format('Y-m-d'),
                ]);

                if (empty($conflicts)) {
                    return [
                        'time_slot' => $timeSlot,
                        'room' => $room,
                        'start_date' => $courseStartDate,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Obtenir la prochaine date pour un jour de la semaine donné
     */
    protected function getNextDateForDay(Carbon $startDate, string $dayOfWeek): Carbon
    {
        $daysMap = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        $targetDay = $daysMap[$dayOfWeek] ?? Carbon::MONDAY;
        $date = $startDate->copy();

        // Si on est déjà le bon jour, utiliser cette date
        if ($date->dayOfWeek === $targetDay) {
            return $date;
        }

        // Sinon, trouver le prochain jour correspondant
        return $date->next($targetDay);
    }

    /**
     * Optimiser un emploi du temps existant
     */
    public function optimizeSchedule(int $academicYearId): array
    {
        // TODO: Implémenter l'optimisation
        // - Minimiser les trous dans l'emploi du temps
        // - Équilibrer la charge des professeurs
        // - Optimiser l'utilisation des salles
        
        return [
            'success' => true,
            'message' => 'Optimisation non encore implémentée',
        ];
    }
}

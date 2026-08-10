<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use App\Modules\Core\Services\PdfService;
use Carbon\Carbon;

class SchedulePDFService
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Générer le PDF de l'emploi du temps pour un groupe de classe
     */
    public function generateClassGroupSchedulePDF(int $classGroupId, ?string $startDate = null, ?string $endDate = null)
    {
        \Log::info('=== GÉNÉRATION PDF EMPLOI DU TEMPS ===');
        \Log::info('Class Group ID: ' . $classGroupId);
        \Log::info('Start Date: ' . ($startDate ?? 'null'));
        \Log::info('End Date: ' . ($endDate ?? 'null'));

        $query = ScheduledCourse::with([
            'timeSlot',
            'room.building',
            'program.classGroup.department.cycle',
            'program.classGroup.academicYear',
            'program.courseElementProfessor.courseElement.teachingUnit',
            'program.courseElementProfessor.professor'
        ])
        ->whereHas('program', function ($q) use ($classGroupId) {
            $q->where('class_group_id', $classGroupId);
        })
        ->where('is_cancelled', false);

        if ($startDate) {
            $query->whereDate('start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        $scheduledCourses = $query->get();
        \Log::info('Nombre de cours trouvés: ' . $scheduledCourses->count());

        $classGroup = $scheduledCourses->first()?->program?->classGroup;

        if (!$classGroup) {
            \Log::error('Groupe de classe non trouvé');
            throw new \Exception('Groupe de classe non trouvé');
        }

        \Log::info('Groupe de classe: ' . $classGroup->name);
        \Log::info('Département: ' . ($classGroup->department->name ?? 'N/A'));
        \Log::info('Cycle: ' . ($classGroup->department->cycle->name ?? 'N/A'));

        // Déterminer le cycle (Licence ou Ingénieur)
        $cycleName = $classGroup->department->cycle->name ?? '';
        $isLicence = stripos($cycleName, 'licence') !== false;

        \Log::info('Type de cycle détecté: ' . ($isLicence ? 'Licence' : 'Ingénieur'));

        if ($isLicence) {
            return $this->generateLicencePDF($classGroup, $scheduledCourses, $startDate, $endDate);
        } else {
            return $this->generateIngenieurPDF($classGroup, $scheduledCourses, $startDate, $endDate);
        }
    }

    /**
     * Générer le PDF pour le cycle Ingénieur
     */
    private function generateIngenieurPDF($classGroup, $scheduledCourses, ?string $startDate, ?string $endDate)
    {
        $schedule = $this->organizeSchedule($scheduledCourses);

        $data = [
            'classGroup' => $classGroup,
            'schedule' => $schedule,
            'startDate' => $startDate ? Carbon::parse($startDate)->format('d/m/Y') : null,
            'endDate' => $endDate ? Carbon::parse($endDate)->format('d/m/Y') : null,
            'anneeAcamedique' => $classGroup->academicYear->libelle ?? $classGroup->academicYear->academic_year ?? '',
        ];

        return $this->pdfService->generateWithTemplate('emploi-du-temps-classe', $data, [
            'orientation' => 'landscape',
            'paper_size' => 'A4'
        ]);
    }

    /**
     * Générer le PDF pour le cycle Licence (format par semaine avec dates)
     */
    private function generateLicencePDF($classGroup, $scheduledCourses, ?string $startDate, ?string $endDate)
    {
        \Log::info('=== GÉNÉRATION PDF LICENCE ===');
        
        // Organiser par dates spécifiques
        $scheduleByDate = $this->organizeScheduleByDate($scheduledCourses);
        \Log::info('Cours organisés par date: ' . count($scheduleByDate) . ' dates');
        
        // Grouper par semaines
        $scheduleByWeek = $this->groupByWeeks($scheduleByDate, $startDate, $endDate);
        \Log::info('Nombre de semaines: ' . count($scheduleByWeek));

        $data = [
            'classGroup' => $classGroup,
            'scheduleByWeek' => $scheduleByWeek,
            'startDate' => $startDate ? Carbon::parse($startDate)->format('d/m/Y') : null,
            'endDate' => $endDate ? Carbon::parse($endDate)->format('d/m/Y') : null,
            'anneeAcamedique' => $classGroup->academicYear->libelle ?? $classGroup->academicYear->academic_year ?? '',
            'departmentName' => $classGroup->department->name ?? 'N/A',
            'cycleName' => $classGroup->department->cycle->name ?? 'N/A',
            'level' => $classGroup->level ?? 'N/A',
        ];

        \Log::info('Données PDF:', [
            'classe' => $classGroup->name,
            'département' => $data['departmentName'],
            'cycle' => $data['cycleName'],
            'niveau' => $data['level'],
            'année_académique' => $data['anneeAcamedique'],
        ]);

        return $this->pdfService->generateWithTemplate('emploi-du-temps-licence', $data, [
            'orientation' => 'landscape',
            'paper_size' => 'A4'
        ]);
    }

    /**
     * Organiser les cours par date spécifique
     */
    private function organizeScheduleByDate($scheduledCourses)
    {
        $schedule = [];

        foreach ($scheduledCourses as $course) {
            if (!$course->timeSlot) continue;

            $date = Carbon::parse($course->start_date)->format('Y-m-d');
            $timeKey = $course->timeSlot->start_time . '-' . $course->timeSlot->end_time;

            if (!isset($schedule[$date])) {
                $schedule[$date] = [];
            }

            if (!isset($schedule[$date][$timeKey])) {
                $schedule[$date][$timeKey] = [
                    'time' => [
                        'start' => $course->timeSlot->start_time,
                        'end' => $course->timeSlot->end_time,
                    ],
                    'courses' => []
                ];
            }

            $schedule[$date][$timeKey]['courses'][] = $course;
        }

        return $schedule;
    }

    /**
     * Grouper les cours par semaines
     */
    private function groupByWeeks($scheduleByDate, ?string $startDate, ?string $endDate)
    {
        \Log::info('groupByWeeks appelé', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dates_count' => count($scheduleByDate)
        ]);

        if (!$startDate || !$endDate) {
            \Log::info('Pas de dates fournies, formatage en une seule semaine');
            return $this->formatSingleWeek($scheduleByDate);
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $weeks = [];
        $weekNumber = 1;

        $currentWeekStart = $start->copy();
        
        while ($currentWeekStart->lte($end)) {
            $currentWeekEnd = $currentWeekStart->copy()->addDays(6);
            if ($currentWeekEnd->gt($end)) {
                $currentWeekEnd = $end->copy();
            }

            $weekDates = [];
            $weekSchedule = [];
            
            // Générer les dates de la semaine
            $current = $currentWeekStart->copy();
            while ($current->lte($currentWeekEnd)) {
                $dateKey = $current->format('Y-m-d');
                $weekDates[$dateKey] = [
                    'date' => $current->format('d/m/Y'),
                    'day_name' => $this->getDayName($current->dayOfWeek),
                ];
                $current->addDay();
            }

            // Organiser les cours de cette semaine
            $timeSlots = [];
            foreach ($scheduleByDate as $date => $slots) {
                if (isset($weekDates[$date])) {
                    foreach ($slots as $timeKey => $data) {
                        if (!isset($timeSlots[$timeKey])) {
                            $timeSlots[$timeKey] = [
                                'time' => $data['time'],
                                'courses' => []
                            ];
                        }
                        $timeSlots[$timeKey]['courses'][$date] = $data['courses'];
                    }
                }
            }

            if (!empty($timeSlots)) {
                $weeks[$weekNumber] = [
                    'title' => $weekNumber === 1 ? '1ère Semaine' : $weekNumber . 'ème Semaine',
                    'dates' => $weekDates,
                    'timeSlots' => $timeSlots,
                ];
                $weekNumber++;
            }

            $currentWeekStart->addWeek();
        }

        return $weeks;
    }

    /**
     * Formater une seule semaine (quand pas de dates fournies)
     */
    private function formatSingleWeek($scheduleByDate)
    {
        if (empty($scheduleByDate)) {
            \Log::warning('Aucune donnée de cours à formater');
            return [];
        }

        // Trouver la plage de dates dans les cours
        $dates = array_keys($scheduleByDate);
        sort($dates);
        
        $firstDate = Carbon::parse($dates[0]);
        $lastDate = Carbon::parse($dates[count($dates) - 1]);
        
        \Log::info('Formatage semaine unique', [
            'première_date' => $firstDate->format('Y-m-d'),
            'dernière_date' => $lastDate->format('Y-m-d')
        ]);

        // Déterminer le lundi de la première semaine
        $weekStart = $firstDate->copy()->startOfWeek();
        $weekEnd = $lastDate->copy()->endOfWeek();

        $weeks = [];
        $weekNumber = 1;
        $currentWeekStart = $weekStart->copy();

        while ($currentWeekStart->lte($weekEnd)) {
            $currentWeekEnd = $currentWeekStart->copy()->addDays(6);

            $weekDates = [];
            
            // Générer les dates de la semaine (lundi à dimanche)
            $current = $currentWeekStart->copy();
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $current->format('Y-m-d');
                $weekDates[$dateKey] = [
                    'date' => $current->format('d/m/Y'),
                    'day_name' => $this->getDayName($current->dayOfWeek),
                ];
                $current->addDay();
            }

            // Organiser les cours de cette semaine
            $timeSlots = [];
            foreach ($scheduleByDate as $date => $slots) {
                if (isset($weekDates[$date])) {
                    foreach ($slots as $timeKey => $data) {
                        if (!isset($timeSlots[$timeKey])) {
                            $timeSlots[$timeKey] = [
                                'time' => $data['time'],
                                'courses' => []
                            ];
                        }
                        $timeSlots[$timeKey]['courses'][$date] = $data['courses'];
                    }
                }
            }

            if (!empty($timeSlots)) {
                $weeks[$weekNumber] = [
                    'title' => $weekNumber === 1 ? '1ère Semaine' : $weekNumber . 'ème Semaine',
                    'dates' => $weekDates,
                    'timeSlots' => $timeSlots,
                ];
                \Log::info("Semaine $weekNumber créée avec " . count($timeSlots) . " créneaux");
                $weekNumber++;
            }

            $currentWeekStart->addWeek();
        }

        \Log::info('Total semaines créées: ' . count($weeks));
        return $weeks;
    }

    /**
     * Obtenir le nom du jour en français
     */
    private function getDayName($dayOfWeek)
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $days[$dayOfWeek] ?? '';
    }

    /**
     * Générer le PDF de l'emploi du temps pour un professeur
     */
    public function generateProfessorSchedulePDF(int $professorId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = ScheduledCourse::with([
            'timeSlot',
            'room.building',
            'program.classGroup.department',
            'program.courseElementProfessor.courseElement',
            'program.courseElementProfessor.professor'
        ])
        ->whereHas('program.courseElementProfessor', function ($q) use ($professorId) {
            $q->where('professor_id', $professorId);
        })
        ->where('is_cancelled', false);

        if ($startDate) {
            $query->whereDate('start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        $scheduledCourses = $query->get();
        $professor = $scheduledCourses->first()?->program?->courseElementProfessor?->professor;

        if (!$professor) {
            throw new \Exception('Professeur non trouvé');
        }

        $schedule = $this->organizeSchedule($scheduledCourses);

        $data = [
            'professor' => $professor,
            'schedule' => $schedule,
            'startDate' => $startDate ? Carbon::parse($startDate)->format('d/m/Y') : null,
            'endDate' => $endDate ? Carbon::parse($endDate)->format('d/m/Y') : null,
            'anneeAcamedique' => $scheduledCourses->first()?->program?->classGroup?->academicYear->libelle ?? '',
        ];

        return $this->pdfService->generateWithTemplate('emploi-du-temps-professeur', $data, [
            'orientation' => 'landscape',
            'paper_size' => 'A4'
        ]);
    }

    /**
     * Organiser les cours par jour et heure
     */
    private function organizeSchedule($scheduledCourses)
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $schedule = [];

        // Collecter tous les créneaux horaires uniques
        $timeSlots = [];
        foreach ($scheduledCourses as $course) {
            if ($course->timeSlot) {
                $key = $course->timeSlot->start_time . '-' . $course->timeSlot->end_time;
                if (!isset($timeSlots[$key])) {
                    $timeSlots[$key] = [
                        'start' => $course->timeSlot->start_time,
                        'end' => $course->timeSlot->end_time,
                    ];
                }
            }
        }

        // Trier les créneaux
        uksort($timeSlots, function ($a, $b) {
            return strcmp($a, $b);
        });

        // Créer la grille
        foreach ($timeSlots as $key => $slot) {
            $schedule[$key] = [
                'time' => $slot,
                'days' => []
            ];

            foreach ($days as $day) {
                $schedule[$key]['days'][$day] = [];
            }
        }

        // Remplir la grille
        foreach ($scheduledCourses as $course) {
            if ($course->timeSlot) {
                $key = $course->timeSlot->start_time . '-' . $course->timeSlot->end_time;
                $day = $course->timeSlot->day_of_week;

                if (isset($schedule[$key]['days'][$day])) {
                    $schedule[$key]['days'][$day][] = $course;
                }
            }
        }

        return $schedule;
    }
}

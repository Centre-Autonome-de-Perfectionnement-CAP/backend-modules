<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use App\Modules\EmploiDuTemps\Http\Requests\CreateScheduledCourseRequest;
use App\Modules\EmploiDuTemps\Http\Requests\UpdateScheduledCourseRequest;
use App\Modules\EmploiDuTemps\Http\Resources\ScheduledCourseResource;
use App\Modules\EmploiDuTemps\Services\ScheduledCourseService;
use App\Modules\EmploiDuTemps\Services\ConflictDetectionService;
use App\Modules\EmploiDuTemps\Services\ScheduleGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Exception;

class ScheduledCourseController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected ScheduledCourseService $scheduledCourseService,
        protected ConflictDetectionService $conflictDetectionService,
        protected ScheduleGeneratorService $scheduleGeneratorService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'program_id',
            'time_slot_id',
            'room_id',
            'class_group_id',
            'professor_id',
            'start_date',
            'end_date',
            'is_cancelled',
            'is_recurring',
            'sort_by',
            'sort_order'
        ]);
        $perPage = $this->getPerPage($request);
        
        $scheduledCourses = $this->scheduledCourseService->getAll($filters, $perPage);

        $transformedData = ScheduledCourseResource::collection($scheduledCourses->items());
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedData,
            $scheduledCourses->total(),
            $scheduledCourses->perPage(),
            $scheduledCourses->currentPage(),
            ['path' => $request->url()]
        );

        return $this->successPaginatedResponse(
            $paginator,
            'Cours planifiés récupérés avec succès'
        );
    }

    public function store(CreateScheduledCourseRequest $request): JsonResponse
    {
        try {
            $scheduledCourse = $this->scheduledCourseService->create($request->validated());

            return $this->createdResponse(
                new ScheduledCourseResource($scheduledCourse->load([
                    'timeSlot',
                    'room.building',
                    'program.classGroup',
                    'program.courseElementProfessor.courseElement',
                    'program.courseElementProfessor.professor'
                ])),
                'Cours planifié créé avec succès'
            );
        } catch (Exception $e) {
            // Gérer les conflits
            $errorData = json_decode($e->getMessage(), true);
            if (is_array($errorData) && isset($errorData['conflicts'])) {
                return $this->errorResponse(
                    $errorData['message'],
                    422,
                    $errorData
                );
            }
            
            throw $e;
        }
    }

    public function show(ScheduledCourse $scheduledCourse): JsonResponse
    {
        return $this->successResponse(
            new ScheduledCourseResource($scheduledCourse->load([
                'timeSlot',
                'room.building',
                'program.classGroup',
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor'
            ])),
            'Cours planifié récupéré avec succès'
        );
    }

    public function update(UpdateScheduledCourseRequest $request, ScheduledCourse $scheduledCourse): JsonResponse
    {
        try {
            $scheduledCourse = $this->scheduledCourseService->update($scheduledCourse, $request->validated());

            return $this->updatedResponse(
                new ScheduledCourseResource($scheduledCourse),
                'Cours planifié mis à jour avec succès'
            );
        } catch (Exception $e) {
            // Gérer les conflits
            $errorData = json_decode($e->getMessage(), true);
            if (is_array($errorData) && isset($errorData['conflicts'])) {
                return $this->errorResponse(
                    $errorData['message'],
                    422,
                    $errorData
                );
            }
            
            throw $e;
        }
    }

    public function destroy(ScheduledCourse $scheduledCourse): JsonResponse
    {
        $this->scheduledCourseService->delete($scheduledCourse);

        return $this->deletedResponse('Cours planifié supprimé avec succès');
    }

    /**
     * Vérifier les conflits pour un cours planifié
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'room_id' => 'required|exists:rooms,id',
            'start_date' => 'required|date',
            'is_recurring' => 'boolean',
            'recurrence_end_date' => 'nullable|date|after:start_date',
            'scheduled_course_id' => 'nullable|exists:scheduled_courses,id',
        ]);

        $conflicts = $this->conflictDetectionService->detectConflicts(
            $request->only(['program_id', 'time_slot_id', 'room_id', 'start_date', 'is_recurring', 'recurrence_end_date']),
            $request->scheduled_course_id
        );

        if (empty($conflicts)) {
            return $this->successResponse(
                ['has_conflicts' => false],
                'Aucun conflit détecté'
            );
        }

        return $this->successResponse(
            [
                'has_conflicts' => true,
                'conflicts' => $conflicts,
            ],
            'Des conflits ont été détectés'
        );
    }

    /**
     * Annuler un cours planifié
     */
    public function cancel(Request $request, ScheduledCourse $scheduledCourse): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $scheduledCourse = $this->scheduledCourseService->cancel(
            $scheduledCourse,
            $request->notes
        );

        return $this->successResponse(
            new ScheduledCourseResource($scheduledCourse),
            'Cours annulé avec succès'
        );
    }

    /**
     * Mettre à jour les heures effectuées
     */
    public function updateHours(Request $request, ScheduledCourse $scheduledCourse): JsonResponse
    {
        $request->validate([
            'hours_completed' => 'required|numeric|min:0',
        ]);

        $scheduledCourse = $this->scheduledCourseService->updateCompletedHours(
            $scheduledCourse,
            $request->hours_completed
        );

        return $this->successResponse(
            new ScheduledCourseResource($scheduledCourse),
            'Heures effectuées mises à jour avec succès'
        );
    }

    /**
     * Récupérer l'emploi du temps d'un groupe de classe
     */
    public function getByClassGroup(Request $request, int $classGroupId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $schedule = $this->scheduledCourseService->getScheduleByClassGroup(
            $classGroupId,
            $request->start_date,
            $request->end_date
        );

        return $this->successResponse(
            ScheduledCourseResource::collection($schedule),
            'Emploi du temps récupéré avec succès'
        );
    }

    /**
     * Récupérer l'emploi du temps d'un professeur
     */
    public function getByProfessor(Request $request, int $professorId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $schedule = $this->scheduledCourseService->getScheduleByProfessor(
            $professorId,
            $request->start_date,
            $request->end_date
        );

        return $this->successResponse(
            ScheduledCourseResource::collection($schedule),
            'Emploi du temps du professeur récupéré avec succès'
        );
    }

    /**
     * Récupérer l'emploi du temps d'une salle
     */
    public function getByRoom(Request $request, int $roomId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $schedule = $this->scheduledCourseService->getScheduleByRoom(
            $roomId,
            $request->start_date,
            $request->end_date
        );

        return $this->successResponse(
            ScheduledCourseResource::collection($schedule),
            'Emploi du temps de la salle récupéré avec succès'
        );
    }

    /**
     * Exclure une date d'un cours récurrent
     */
    public function excludeDate(Request $request, ScheduledCourse $scheduledCourse): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $scheduledCourse = $this->scheduledCourseService->excludeDate(
            $scheduledCourse,
            $request->date
        );

        return $this->successResponse(
            new ScheduledCourseResource($scheduledCourse),
            'Date exclue avec succès'
        );
    }

    /**
     * Récupérer les occurrences d'un cours récurrent
     */
    public function getOccurrences(ScheduledCourse $scheduledCourse): JsonResponse
    {
        if (!$scheduledCourse->is_recurring) {
            return $this->errorResponse('Ce cours n\'est pas récurrent', 422);
        }

        $occurrences = $scheduledCourse->getOccurrences();

        return $this->successResponse(
            [
                'occurrences' => array_map(fn($date) => $date->format('Y-m-d'), $occurrences),
                'total_occurrences' => count($occurrences),
            ],
            'Occurrences récupérées avec succès'
        );
    }

    /**
     * Reconduire l'emploi du temps d'une année vers une autre
     */
    public function renewSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'source_academic_year_id' => 'required|exists:academic_years,id',
            'target_academic_year_id' => 'required|exists:academic_years,id|different:source_academic_year_id',
            'date_offset' => 'nullable|integer|min:1',
            'check_conflicts' => 'nullable|boolean',
            'ignore_conflicts' => 'nullable|boolean',
        ]);

        try {
            $result = $this->scheduledCourseService->renewSchedule(
                $request->source_academic_year_id,
                $request->target_academic_year_id,
                [
                    'date_offset' => $request->date_offset ?? 365,
                    'check_conflicts' => $request->check_conflicts ?? true,
                    'ignore_conflicts' => $request->ignore_conflicts ?? false,
                ]
            );

            return $this->successResponse(
                $result,
                "Reconduction terminée : {$result['created']} cours créés, {$result['skipped']} ignorés"
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la reconduction : ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Générer automatiquement un emploi du temps
     */
    public function generateSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'start_date' => 'nullable|date',
        ]);

        try {
            $result = $this->scheduleGeneratorService->generateSchedule(
                $request->academic_year_id,
                [
                    'start_date' => $request->start_date,
                ]
            );

            return $this->successResponse(
                $result,
                "Génération terminée : {$result['created']} cours créés, {$result['skipped']} ignorés"
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la génération : ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Télécharger l'emploi du temps d'un groupe en PDF
     */
    public function downloadClassGroupSchedulePDF(int $classGroupId, Request $request)
    {
        $pdfService = app(\App\Modules\EmploiDuTemps\Services\SchedulePDFService::class);
        
        $pdf = $pdfService->generateClassGroupSchedulePDF(
            $classGroupId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        // Récupérer les infos du groupe pour le nom du fichier
        $classGroup = \App\Modules\Inscription\Models\ClassGroup::with('department.cycle')->find($classGroupId);
        $fileName = 'emploi-du-temps-' . 
                    ($classGroup->department->cycle->name ?? 'classe') . '-' . 
                    ($classGroup->name ?? $classGroupId) . '-' . 
                    date('Y-m-d') . '.pdf';
        $fileName = str_replace(' ', '-', strtolower($fileName));

        \Log::info('Téléchargement PDF: ' . $fileName);

        return $pdf->download($fileName);
    }

    /**
     * Télécharger l'emploi du temps d'un professeur en PDF
     */
    public function downloadProfessorSchedulePDF(int $professorId, Request $request)
    {
        $pdfService = app(\App\Modules\EmploiDuTemps\Services\SchedulePDFService::class);
        
        $pdf = $pdfService->generateProfessorSchedulePDF(
            $professorId,
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $pdf->download('emploi-du-temps-professeur-' . $professorId . '.pdf');
    }

    /**
     * Télécharger l'emploi du temps d'une salle en PDF
     */
    public function downloadRoomSchedulePDF(int $roomId, Request $request)
    {
        // TODO: Implémenter la génération PDF pour les salles
        return response()->json(['message' => 'Fonctionnalité en cours de développement'], 501);
    }

    /**
     * Créer plusieurs cours en masse (mode brouillon)
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'courses' => 'required|array|min:1',
            'courses.*.program_id' => 'required|exists:programs,id',
            'courses.*.time_slot_id' => 'required|exists:time_slots,id',
            'courses.*.room_id' => 'required|exists:rooms,id',
            'courses.*.start_date' => 'required|date',
            'courses.*.total_hours' => 'required|numeric|min:0',
            'courses.*.is_recurring' => 'boolean',
            'courses.*.recurrence_end_date' => 'nullable|date|after:courses.*.start_date',
            'courses.*.notes' => 'nullable|string',
        ]);

        try {
            $created = [];
            $errors = [];
            $conflicts = [];

            DB::beginTransaction();

            foreach ($request->courses as $index => $courseData) {
                try {
                    // Vérifier les conflits
                    $courseConflicts = $this->conflictDetectionService->detectConflicts($courseData);
                    
                    if (!empty($courseConflicts)) {
                        $conflicts[] = [
                            'index' => $index,
                            'data' => $courseData,
                            'conflicts' => $courseConflicts,
                        ];
                        continue;
                    }

                    // Créer le cours
                    $scheduledCourse = $this->scheduledCourseService->create($courseData);
                    $created[] = new ScheduledCourseResource($scheduledCourse->load([
                        'timeSlot',
                        'room.building',
                        'program.classGroup.department',
                        'program.classGroup.academicYear',
                        'program.courseElementProfessor.courseElement.teachingUnit',
                        'program.courseElementProfessor.professor'
                    ]));
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $courseData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (!empty($conflicts) || !empty($errors)) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Certains cours n\'ont pas pu être créés',
                    'data' => [
                        'created' => [],
                        'conflicts' => $conflicts,
                        'errors' => $errors,
                        'summary' => [
                            'total' => count($request->courses),
                            'created_count' => 0,
                            'conflict_count' => count($conflicts),
                            'error_count' => count($errors),
                        ],
                    ],
                ], 422);
            }

            DB::commit();

            return $this->successResponse([
                'created' => $created,
                'summary' => [
                    'total' => count($request->courses),
                    'created_count' => count($created),
                    'conflict_count' => 0,
                    'error_count' => 0,
                ],
            ], count($created) . ' cours créés avec succès');

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Erreur lors de la création en masse : ' . $e->getMessage(),
                500
            );
        }
    }
}

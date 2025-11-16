<?php

namespace App\Modules\Cours\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cours\Models\Program;
use App\Modules\Cours\Http\Requests\CreateProgramRequest;
use App\Modules\Cours\Http\Requests\UpdateProgramRequest;
use App\Modules\Cours\Http\Resources\ProgramResource;
use App\Modules\Cours\Services\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class ProgramController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected ProgramService $programService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Liste tous les programmes
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'class_group_id',
            'course_element_id',
            'professor_id',
            'search',
            'sort_by',
            'sort_order'
        ]);
        $perPage = $this->getPerPage($request);
        
        $programs = $this->programService->getAll($filters, $perPage);

        $programs->setCollection(
            ProgramResource::collection($programs->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $programs,
            'Programmes récupérés avec succès'
        );
    }

    /**
     * Créer un nouveau programme
     */
    public function store(CreateProgramRequest $request): JsonResponse
    {
        $program = $this->programService->create($request->validated());

        return $this->createdResponse(
            new ProgramResource($program),
            'Programme créé avec succès'
        );
    }

    /**
     * Afficher les détails d'un programme
     */
    public function show(Program $program): JsonResponse
    {
        $program->load([
            'classGroup',
            'courseElementProfessor.courseElement.teachingUnit',
            'courseElementProfessor.professor'
        ]);

        return $this->successResponse(
            new ProgramResource($program),
            'Programme récupéré avec succès'
        );
    }

    /**
     * Mettre à jour un programme
     */
    public function update(UpdateProgramRequest $request, Program $program): JsonResponse
    {
        $program = $this->programService->update($program, $request->validated());

        return $this->updatedResponse(
            new ProgramResource($program),
            'Programme mis à jour avec succès'
        );
    }

    /**
     * Supprimer un programme
     */
    public function destroy(Program $program): JsonResponse
    {
        $this->programService->delete($program);

        return $this->deletedResponse('Programme supprimé avec succès');
    }

    /**
     * Récupérer l'emploi du temps d'un groupe de classe
     */
    public function getByClassGroup(int $classGroupId, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request, 50);
        $programs = $this->programService->getProgramsByClassGroup($classGroupId, $perPage);

        $programs->setCollection(
            ProgramResource::collection($programs->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $programs,
            'Emploi du temps récupéré avec succès'
        );
    }

    /**
     * Récupérer les programmes d'un professeur
     */
    public function getByProfessor(int $professorId, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request, 50);
        $programs = $this->programService->getProgramsByProfessor($professorId, $perPage);

        $programs->setCollection(
            ProgramResource::collection($programs->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $programs,
            'Programmes du professeur récupérés avec succès'
        );
    }

    /**
     * Récupérer les programmes d'un élément de cours
     */
    public function getByCourseElement(int $courseElementId, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request, 50);
        $programs = $this->programService->getProgramsByCourseElement($courseElementId, $perPage);

        $programs->setCollection(
            ProgramResource::collection($programs->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $programs,
            'Programmes de l\'élément de cours récupérés avec succès'
        );
    }

    /**
     * Créer plusieurs programmes en masse
     */
    public function bulkStore(\App\Modules\Cours\Http\Requests\BulkCreateProgramsRequest $request): JsonResponse
    {
        $result = $this->programService->bulkCreate($request->validated()['programs']);

        $message = "{$result['success_count']} programme(s) créé(s) avec succès";
        if ($result['error_count'] > 0) {
            $message .= ", {$result['error_count']} erreur(s)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'created' => ProgramResource::collection($result['created']),
                'errors' => $result['errors'],
                'summary' => [
                    'success_count' => $result['success_count'],
                    'error_count' => $result['error_count'],
                    'total' => $result['success_count'] + $result['error_count'],
                ],
            ],
        ], 201);
    }

    /**
     * Copier les programmes d'une classe à une autre
     * Utile pour dupliquer l'emploi du temps d'une année à une autre
     */
    public function copyPrograms(\App\Modules\Cours\Http\Requests\CopyProgramsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->programService->copyPrograms(
            $validated['source_class_group_id'],
            $validated['target_class_group_id']
        );

        if (isset($result['message'])) {
            return $this->successResponse(null, $result['message']);
        }

        $message = "{$result['success_count']} programme(s) copié(s) avec succès";
        if ($result['skipped_count'] > 0) {
            $message .= ", {$result['skipped_count']} ignoré(s) (déjà existants)";
        }
        if ($result['error_count'] > 0) {
            $message .= ", {$result['error_count']} erreur(s)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'created' => ProgramResource::collection($result['created']),
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'summary' => [
                    'total_source' => $result['total_source'],
                    'success_count' => $result['success_count'],
                    'skipped_count' => $result['skipped_count'],
                    'error_count' => $result['error_count'],
                ],
            ],
        ], 201);
    }

    public function renewForNextYear(Request $request): JsonResponse
    {
        $request->validate([
            'current_academic_year_id' => 'required|exists:academic_years,id',
            'next_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $result = $this->programService->renewForNextYear(
            $request->current_academic_year_id,
            $request->next_academic_year_id
        );

        return $this->successResponse($result, "{$result['created']} programme(s) reconduit(s)");
    }
}

<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\LmdGradeService;
use App\Modules\Notes\Services\OldGradeService;
use App\Modules\Notes\Http\Requests\GetGradeSheetRequest;
use App\Modules\Notes\Http\Requests\AddColumnRequest;
use App\Modules\Notes\Http\Requests\UpdateSingleGradeRequest;
use App\Modules\Notes\Http\Requests\SetWeightingRequest;
use App\Modules\Cours\Models\Program;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessorGradeController extends Controller
{
    use ApiResponse, HasPagination;

    private LmdGradeService $lmdGradeService;
    private OldGradeService $oldGradeService;

    public function __construct(LmdGradeService $lmdGradeService, OldGradeService $oldGradeService)
    {
        $this->lmdGradeService = $lmdGradeService;
        $this->oldGradeService = $oldGradeService;
    }

    /**
     * Obtient les classes d'un professeur regroupées par cycle
     */
    public function getMyClasses(Request $request): JsonResponse
    {
        // L'utilisateur connecté est le professeur lui-même
        $professorId = $request->user()->id;
        $academicYearId = $request->input('academic_year_id');
        $departmentId = $request->input('department_id');
        $cohort = $request->input('cohort');

        if (!$professorId) {
            return $this->notFoundResponse('Professeur non trouvé');
        }

        $result = $this->lmdGradeService->getProfessorClassesByCycle(
            $professorId, 
            $academicYearId, 
            $departmentId,
            $cohort
        );

        return $this->successResponse($result, 'Classes récupérées avec succès');
    }

    /**
     * Obtient les programmes d'une classe pour un professeur
     */
    public function getProgramsByClass(Request $request, int $classGroupId): JsonResponse
    {
        // L'utilisateur connecté est le professeur lui-même
        $professorId = $request->user()->id;

        if (!$professorId) {
            return $this->notFoundResponse('Professeur non trouvé');
        }

        $result = $this->lmdGradeService->getProgramsByClass($professorId, $classGroupId);

        return $this->successResponse($result, 'Programmes récupérés avec succès');
    }

    /**
     * Obtient la fiche de notation pour un programme
     */
    public function getGradeSheet(GetGradeSheetRequest $request): JsonResponse
    {
        $program = Program::with(['classGroup.cycle', 'courseElementProfessor.courseElement'])
            ->findOrFail($request->program_id);

        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;
        
        $result = $service->getGradeSheet($program, $request->input('cohort'));

        return $this->successResponse($result, 'Fiche de notation récupérée avec succès');
    }

    /**
     * Crée une nouvelle évaluation (colonne de notes)
     */
    public function createEvaluation(AddColumnRequest $request): JsonResponse
    {
        $program = Program::with('classGroup.cycle')->findOrFail($request->program_id);
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->createEvaluation(
            $request->program_id,
            $request->notes,
            $request->boolean('is_retake', false)
        );

        return $this->createdResponse($result, 'Évaluation créée avec succès');
    }

    /**
     * Met à jour une note individuelle
     */
    public function updateGrade(UpdateSingleGradeRequest $request): JsonResponse
    {
        $program = Program::with('classGroup.cycle')->findOrFail($request->program_id);
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->updateSingleGrade(
            $request->student_pending_student_id,
            $request->program_id,
            $request->position,
            $request->value
        );

        return $this->updatedResponse($result, 'Note mise à jour avec succès');
    }

    /**
     * Définit la pondération des évaluations
     */
    public function setWeighting(SetWeightingRequest $request): JsonResponse
    {
        $program = Program::with('classGroup.cycle')->findOrFail($request->program_id);
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->setWeighting($request->program_id, $request->weighting);

        return $this->updatedResponse($result, 'Pondération mise à jour avec succès');
    }

    /**
     * Exporte la fiche récapitulative
     */
    public function exportGradeSheet(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'include_retake' => 'boolean'
        ]);

        $program = Program::with('classGroup.cycle')->findOrFail($request->program_id);
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->exportGradeSheet(
            $request->program_id,
            $request->boolean('include_retake', false)
        );

        return $this->successResponse($result, 'Fiche exportée avec succès');
    }

    /**
     * Duplique une note pour tous les étudiants
     */
    public function duplicateGrade(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'position' => 'required|integer|min:0',
            'value' => 'required|numeric|min:-1|max:20'
        ]);

        $program = Program::with('classGroup.cycle')->findOrFail($request->program_id);
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->duplicateGrade(
            $request->program_id,
            $request->position,
            $request->value
        );

        return $this->updatedResponse($result, 'Note dupliquée avec succès');
    }
}
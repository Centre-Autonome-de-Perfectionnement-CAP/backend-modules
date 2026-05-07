<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\OldGradeService;
use App\Modules\Cours\Models\Program;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OldGradeController extends Controller
{
    use ApiResponse;

    private OldGradeService $gradeService;

    public function __construct(OldGradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    /**
     * Obtient la fiche de notation
     */
    public function getGradeSheet(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'cohort' => 'nullable|string',
        ]);

        $program = Program::with(['classGroup', 'courseElementProfessor.courseElement', 'courseElementProfessor.professor'])
            ->findOrFail($request->program_id);
        
        $cohort = $request->input('cohort');
        $students = $this->gradeService->getStudentsByProgram($program, $cohort);

        return $this->successResponse([
            'program' => [
                'id' => $program->id,
                'uuid' => $program->uuid,
                'name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                'class_group' => [
                    'id' => $program->classGroup->id,
                    'name' => $program->classGroup->name ?? 'N/A',
                ],
                'weighting' => $program->weighting ?? [],
                'column_count' => count($program->weighting ?? []),
            ],
            'students' => $students,
            'total_students' => $students->count(),
        ], 'Fiche de notation récupérée avec succès');
    }

    /**
     * Ajoute une colonne de notes
     */
    public function addColumn(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'notes' => 'required|array',
            'notes.*' => 'required|numeric|min:0|max:20',
        ]);

        try {
            $results = $this->gradeService->addNoteColumn(
                $request->program_id,
                $request->notes
            );

            $program = Program::findOrFail($request->program_id);

            return $this->createdResponse([
                'column_added' => count($program->weighting ?? []),
                'weighting_updated' => $program->weighting,
                'students_updated' => count($results),
            ], count($results) . ' notes ajoutées avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de l\'ajout des notes: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Modifie une note individuelle
     */
    public function updateSingle(Request $request): JsonResponse
    {
        $request->validate([
            'student_pending_student_id' => 'required|exists:student_pending_students,id',
            'program_id' => 'required|exists:programs,id',
            'position' => 'required|integer|min:0',
            'value' => 'required|numeric|min:0|max:20',
        ]);

        try {
            $success = $this->gradeService->updateNoteAtPosition(
                $request->student_pending_student_id,
                $request->program_id,
                $request->position,
                $request->value
            );

            if ($success) {
                return $this->updatedResponse(null, 'Note modifiée avec succès');
            }

            return $this->notFoundResponse('Note introuvable ou position invalide');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la modification: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Supprime une colonne de notes
     */
    public function deleteColumn(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'column_index' => 'required|integer|min:0',
        ]);

        try {
            $success = $this->gradeService->deleteNoteColumn(
                $request->program_id,
                $request->column_index
            );

            if ($success) {
                $program = Program::findOrFail($request->program_id);

                return $this->successResponse([
                    'weighting_updated' => $program->weighting,
                ], 'Colonne supprimée avec succès');
            }

            return $this->errorResponse('Échec de la suppression', 500);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la suppression: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Définit une pondération manuelle
     */
    public function setWeighting(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'weighting' => 'required|array',
            'weighting.*' => 'required|integer|min:0|max:100',
        ]);

        if (array_sum($request->weighting) !== 100) {
            return $this->validationErrorResponse(
                'La somme de la pondération doit faire 100%'
            );
        }

        try {
            $success = $this->gradeService->setPonderation(
                $request->program_id,
                $request->weighting
            );

            if ($success) {
                return $this->updatedResponse(null, 'Pondération mise à jour avec succès');
            }

            return $this->errorResponse('Échec de la mise à jour', 500);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\LmdGradeService;
use App\Modules\Cours\Models\Program;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmdGradeController extends Controller
{
    use ApiResponse;

    private LmdGradeService $gradeService;

    public function __construct(LmdGradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    /**
     * Obtient la fiche de notation (tous les étudiants avec leurs notes)
     * 
     * GET /api/notes/lmd-grades/grade-sheet?program_id=45&cohort=1
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
                'retake_weighting' => $program->retake_weighting ?? [],
                'column_count' => count($program->weighting ?? []),
            ],
            'students' => $students,
            'total_students' => $students->count(),
        ], 'Fiche de notation récupérée avec succès');
    }

    /**
     * Ajoute une colonne de notes (nouveau devoir)
     * 
     * POST /api/notes/lmd-grades/add-column
     * Body: {"program_id": 45, "notes": {"123": 12.5, "124": 15.0}}
     */
    public function addColumn(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'notes' => 'required|array',
            'notes.*' => 'required|numeric|min:0|max:20',
            'session_normale' => 'sometimes|boolean',
        ]);

        $sessionNormale = $request->input('session_normale', true);

        try {
            $results = $this->gradeService->addNoteColumn(
                $request->program_id,
                $request->notes,
                $sessionNormale
            );

            $program = Program::findOrFail($request->program_id);

            return $this->createdResponse([
                'column_added' => count($program->weighting ?? []),
                'weighting_updated' => $sessionNormale ? $program->weighting : $program->retake_weighting,
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
     * 
     * PUT /api/notes/lmd-grades/update-single
     * Body: {"student_pending_student_id": 123, "program_id": 45, "position": 1, "value": 15.5}
     */
    public function updateSingle(Request $request): JsonResponse
    {
        $request->validate([
            'student_pending_student_id' => 'required|exists:student_pending_students,id',
            'program_id' => 'required|exists:programs,id',
            'position' => 'required|integer|min:0',
            'value' => 'required|numeric|min:0|max:20',
            'session_normale' => 'sometimes|boolean',
        ]);

        $sessionNormale = $request->input('session_normale', true);

        try {
            $success = $this->gradeService->updateNoteAtPosition(
                $request->student_pending_student_id,
                $request->program_id,
                $request->position,
                $request->value,
                $sessionNormale
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
     * Supprime une colonne de notes (devoir entier)
     * 
     * DELETE /api/notes/lmd-grades/delete-column
     * Body: {"program_id": 45, "column_index": 2}
     */
    public function deleteColumn(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'column_index' => 'required|integer|min:0',
            'session_normale' => 'sometimes|boolean',
        ]);

        $sessionNormale = $request->input('session_normale', true);

        try {
            $success = $this->gradeService->deleteNoteColumn(
                $request->program_id,
                $request->column_index,
                $sessionNormale
            );

            if ($success) {
                $program = Program::findOrFail($request->program_id);

                return $this->deletedResponse('Colonne supprimée avec succès')
                    ->setData([
                        'success' => true,
                        'message' => 'Colonne supprimée avec succès',
                        'data' => [
                            'weighting_updated' => $sessionNormale ? $program->weighting : $program->retake_weighting,
                        ],
                    ]);
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
     * 
     * POST /api/notes/lmd-grades/set-weighting
     * Body: {"program_id": 45, "weighting": [40, 30, 30]}
     */
    public function setWeighting(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'weighting' => 'required|array',
            'weighting.*' => 'required|integer|min:0|max:100',
            'session_normale' => 'sometimes|boolean',
        ]);

        // Valide que la somme fait 100
        if (array_sum($request->weighting) !== 100) {
            return $this->validationErrorResponse(
                'La somme de la pondération doit faire 100%'
            );
        }

        $sessionNormale = $request->input('session_normale', true);

        try {
            $success = $this->gradeService->setPonderation(
                $request->program_id,
                $request->weighting,
                $sessionNormale
            );

            if ($success) {
                return $this->updatedResponse(null, 'Pondération mise à jour avec succès');
            }

            return $this->errorResponse('Échec de la mise à jour', 500);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Liste des étudiants en rattrapage (7 <= moyenne < 10)
     * 
     * GET /api/notes/lmd-grades/retake-list?program_id=45&cohort=1
     */
    public function getRetakeList(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'cohort' => 'nullable|string',
        ]);

        $program = Program::findOrFail($request->program_id);
        $cohort = $request->input('cohort');
        $students = $this->gradeService->getStudentsByProgram($program, $cohort);

        // Filtre les étudiants en rattrapage
        $retakeStudents = $students->filter(function ($student) {
            $average = $student['average'];
            return $average !== null && $average >= 7 && $average < 10;
        })->values();

        return $this->successResponse([
            'program_id' => $program->id,
            'students' => $retakeStudents,
            'total' => $retakeStudents->count(),
        ], 'Liste des étudiants en rattrapage récupérée avec succès');
    }
}

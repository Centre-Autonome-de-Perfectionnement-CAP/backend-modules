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
        $academicYearId = $request->input('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;
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
    public function getGradeSheet(Request $request, string $program): JsonResponse
    {
        $programModel = Program::where('uuid', $program)
            ->with(['classGroup.cycle', 'courseElementProfessor.courseElement'])
            ->firstOrFail();

        $isLmd = $programModel->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;
        
        $result = $service->getGradeSheet($programModel, $request->input('cohort'));

        return $this->successResponse($result, 'Fiche de notation récupérée avec succès');
    }

    /**
     * Obtient la liste des étudiants pour créer une évaluation
     */
    public function getStudentsForEvaluation(Request $request, string $program): JsonResponse
    {
        $programModel = Program::where('uuid', $program)
            ->with(['classGroup.cycle', 'classGroup.department', 'courseElementProfessor.courseElement'])
            ->firstOrFail();

        $isLmd = $programModel->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;
        
        $result = $service->getStudentsForEvaluation($programModel, $request->input('cohort'));

        return $this->successResponse($result, 'Étudiants récupérés avec succès');
    }

    /**
     * Crée une nouvelle évaluation (colonne de notes)
     */
    public function createEvaluation(AddColumnRequest $request): JsonResponse
    {
        // Accepter UUID ou ID numérique
        $programId = $request->program_id;
        if (is_string($programId) && strlen($programId) === 36) {
            $program = Program::with('classGroup.cycle')->where('uuid', $programId)->firstOrFail();
        } else {
            $program = Program::with('classGroup.cycle')->findOrFail($programId);
        }
        
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->createEvaluation(
            $program->id,
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
        $programId = $request->program_id;
        if (is_string($programId) && strlen($programId) === 36) {
            $program = Program::with('classGroup.cycle')->where('uuid', $programId)->firstOrFail();
        } else {
            $program = Program::with('classGroup.cycle')->findOrFail($programId);
        }
        
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        // Récupérer le nombre de colonnes depuis les notes de l'étudiant
        $grade = \App\Modules\Notes\Models\LmdSystemGrade::where('student_pending_student_id', $request->student_pending_student_id)
            ->where('program_id', $program->id)
            ->first();
        
        $columnCount = $grade ? count($grade->grades ?? []) : 0;
        $isSessionNormale = $request->position < $columnCount;
        $adjustedPosition = $isSessionNormale ? $request->position : ($request->position - $columnCount);

        $result = $service->updateNoteAtPosition(
            $request->student_pending_student_id,
            $program->id,
            $adjustedPosition,
            $request->value,
            $isSessionNormale
        );

        return $this->updatedResponse($result, 'Note mise à jour avec succès');
    }

    /**
     * Définit la pondération des évaluations
     */
    public function setWeighting(SetWeightingRequest $request): JsonResponse
    {
        $programId = $request->program_id;
        if (is_string($programId) && strlen($programId) === 36) {
            $program = Program::with('classGroup.cycle')->where('uuid', $programId)->firstOrFail();
        } else {
            $program = Program::with('classGroup.cycle')->findOrFail($programId);
        }
        
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->setPonderation($program->id, $request->weighting);

        return $this->updatedResponse($result, 'Pondération mise à jour avec succès');
    }

    /**
     * Exporte la fiche récapitulative en PDF
     */
    public function exportGradeSheet(Request $request, string $program)
    {
        $programModel = Program::with([
            'classGroup.department', 
            'classGroup.cycle', 
            'classGroup.academicYear',
            'courseElementProfessor.courseElement',
            'courseElementProfessor.professor'
        ])->where('uuid', $program)->firstOrFail();
        
        $isLmd = $programModel->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $gradeSheet = $service->getGradeSheet($programModel, $request->cohort);
        
        $includeRetake = $request->boolean('include_retake', false);
        
        $department = $programModel->classGroup->department;
        $academicYear = $programModel->classGroup->academicYear;
        $professor = $programModel->courseElementProfessor->professor ?? null;
        $classeLabel = ($department->abbreviation ?? $department->name) . '-' . $programModel->classGroup->study_level;
        
        $data = [
            'annee' => $academicYear ? $academicYear->academic_year : 'N/A',
            'filiere' => $department->name ?? 'N/A',
            'classe' => $classeLabel,
            'matiere' => $programModel->courseElementProfessor->courseElement->name ?? 'N/A',
            'enseignant' => $professor ? (($professor->last_name ?? '') . ' ' . ($professor->first_names ?? $professor->first_name ?? '')) : 'N/A',
            'column_count' => $gradeSheet['program']['column_count'],
            'weighting' => $gradeSheet['program']['weighting'],
            'include_retake' => $includeRetake,
            'retake_column_count' => $includeRetake ? $gradeSheet['program']['retake_column_count'] : 0,
            'retake_weighting' => $includeRetake ? $gradeSheet['program']['retake_weighting'] : [],
            'etudiants' => $gradeSheet['students']->map(function($student) use ($includeRetake) {
                return (object)[
                    'etudiant' => (object)[
                        'student_id_number' => $student['student_id'] ?? 'N/A',
                        'nom' => $student['last_name'],
                        'prenoms' => $student['first_names']
                    ],
                    'notes' => $student['grades'],
                    'moyenne' => $student['average'],
                    'retake_grades' => $includeRetake ? ($student['retake_grades'] ?? []) : [],
                    'retake_average' => $includeRetake ? ($student['retake_average'] ?? null) : null
                ];
            })
        ];

        $cohort = $request->cohort ?? 'all';
        $dateTime = now()->format('Ymd_His');
        $filename = 'FICHE_NOTES_' . str_replace(['/', '-', ' '], '_', $academicYear->academic_year ?? 'N_A') . '_COHORTE_' . $cohort . '_' . ($department->abbreviation ?? 'N_A') . '_' . $programModel->classGroup->study_level . '_' . $dateTime . '.pdf';
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('core::pdfs.fiche-recapitulatif-notes', $data)
            ->setPaper('a4', 'portrait');
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Duplique une colonne de notes complète
     */
    public function duplicateGrade(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required',
            'column_index' => 'required|integer|min:0',
            'session_normale' => 'boolean'
        ]);

        $programId = $request->program_id;
        if (is_string($programId) && strlen($programId) === 36) {
            $program = Program::with('classGroup.cycle')->where('uuid', $programId)->firstOrFail();
        } else {
            $program = Program::with('classGroup.cycle')->findOrFail($programId);
        }
        
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        $result = $service->duplicateColumn(
            $program->id,
            $request->column_index,
            $request->boolean('session_normale', true)
        );

        return $this->createdResponse($result, 'Colonne dupliquée avec succès');
    }

    /**
     * Supprime une colonne de notes (évaluation)
     */
    public function deleteEvaluation(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required',
            'column_index' => 'required|integer|min:0',
            'session_normale' => 'boolean'
        ]);

        $programId = $request->program_id;
        if (is_string($programId) && strlen($programId) === 36) {
            $program = Program::with('classGroup.cycle')->where('uuid', $programId)->firstOrFail();
        } else {
            $program = Program::with('classGroup.cycle')->findOrFail($programId);
        }
        
        $isLmd = $program->classGroup->cycle->is_lmd ?? false;
        $service = $isLmd ? $this->lmdGradeService : $this->oldGradeService;

        if ($isLmd) {
            $service->deleteNoteColumn(
                $program->id,
                $request->column_index,
                $request->boolean('session_normale', true)
            );
        } else {
            $service->deleteNoteColumn($program->id, $request->column_index);
        }

        return $this->successResponse(null, 'Évaluation supprimée avec succès');
    }
}
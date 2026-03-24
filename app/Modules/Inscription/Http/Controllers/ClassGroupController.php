<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\ClassGroupService;
use App\Modules\Inscription\Http\Requests\StoreClassGroupRequest;
use App\Modules\Inscription\Http\Requests\GetClassGroupsRequest;
use App\Modules\Inscription\Http\Requests\CreateDefaultGroupRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;


class ClassGroupController extends Controller{
    use ApiResponse;

    public function __construct(
        protected ClassGroupService $classGroupService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(GetClassGroupsRequest $request): JsonResponse {
        $groups = $this->classGroupService->getGroups(
            $request->input('academic_year_id'),
            $request->input('department_id'),
            $request->input('study_level'),
            $request->input('cohort')
        );

        return $this->successResponse($groups, 'Groupes récupérés avec succès');
    }


    public function store(StoreClassGroupRequest $request): JsonResponse  {
        $result = $this->classGroupService->createGroups($request->validated());
        
        return $this->createdResponse($result, 'Groupes créés avec succès');
    }

    /**
     * Détails d'un groupe
     */
    public function show(ClassGroup $classGroup): JsonResponse
    {
        $group = $this->classGroupService->getGroupDetails($classGroup);
        
        return $this->successResponse($group, 'Détails du groupe récupérés avec succès');
    }

    /**
     * Supprimer un groupe
     */
    public function destroy(ClassGroup $classGroup): JsonResponse
    {
        $this->classGroupService->deleteGroup($classGroup);
        
        return $this->deletedResponse('Groupe supprimé avec succès');
    }


    private function extractPhone($contacts) {
        if (is_string($contacts)) {
            $contacts = json_decode($contacts, true);
        }

        if (!is_array($contacts)) {
            return null;
        }

        return $contacts['phone'] ?? $contacts['telephone'] ?? null;
    }

    public function getStudents($classGroupId) {
        try {
            \Log::info('=== START getStudents ===', ['classGroupId' => $classGroupId]);

            // récupérer les student_id depuis student_groups
            $studentIds = \App\Modules\Inscription\Models\StudentGroup::where('class_group_id', $classGroupId)
                ->pluck('student_id');

            \Log::info('Student IDs récupérés', ['count' => $studentIds->count(), 'ids' => $studentIds->toArray()]);

            if ($studentIds->isEmpty()) {
                \Log::warning('Aucun étudiant trouvé pour cette classe');
            }

            // récupérer pivot student_pending_student
            $pivotData = \DB::table('student_pending_student')
                ->whereIn('student_id', $studentIds)
                ->get();

            \Log::info('Pivot récupéré', ['count' => $pivotData->count()]);

            // récupérer pending_student_id
            $pendingStudentIds = $pivotData->pluck('pending_student_id');

            // récupérer pending_students avec personalInformation
            $pendingStudents = \App\Modules\Inscription\Models\PendingStudent::whereIn('id', $pendingStudentIds)
                ->with('personalInformation')
                ->get();

            \Log::info('PendingStudents récupérés', ['count' => $pendingStudents->count()]);

            // récupérer students pour matricule
            $studentsData = \App\Modules\Inscription\Models\Student::whereIn('id', $studentIds)
                ->get()
                ->keyBy('id');

            // mapping final
            $students = $pendingStudents->map(function ($pending) use ($pivotData, $studentsData) {

                // chercher le pivot correspondant
                $pivot = $pivotData->first(fn($p) => (int)$p->pending_student_id === (int)$pending->id);
                $studentId = $pivot->student_id ?? null;
                $student = $studentsData[$studentId] ?? null;

                \Log::info('DEBUG MATCH', [
                    'pending_id' => $pending->id,
                    'student_id' => $studentId,
                    'matricule' => $student->student_id_number ?? null
                ]);

                return [
                    'id' => $pending->id,
                    'matricule' => $student->student_id_number ?? null,
                    'nomPrenoms' => trim(
                        ($pending->personalInformation->first_names ?? '') . ' ' .
                        ($pending->personalInformation->last_name ?? '')
                    ),
                    'email' => $pending->personalInformation->email ?? null,
                    'sexe' => $pending->personalInformation->gender ?? null,
                    'redoublant' => $pending->is_repeating ? 'Oui' : 'Non',
                    'telephone' => $this->extractPhone($pending->personalInformation->contacts ?? null)
                ];
            });

            \Log::info('Résultat final', ['count' => $students->count(), 'data' => $students->toArray()]);

            return response()->json(['students' => $students]);

        } catch (\Exception $e) {
            \Log::error('=== ERROR getStudents ===', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function createDefault(CreateDefaultGroupRequest $request): JsonResponse {
        $result = $this->classGroupService->createDefaultGroup(
            $request->input('academic_year_id'),
            $request->input('department_id'),
            $request->input('study_level'),
            $request->input('cohort')
        );
        
        if (!$result) {
            return $this->errorResponse('Aucun étudiant trouvé pour cette cohorte', 404);
        }
        
        return $this->createdResponse($result, 'Groupe unique créé avec succès');
    }

    /**
     * Récupère les groupes d'une classe spécifique (pour création de programmes)
     */
    public function getGroupsByClass(int $classGroupId): JsonResponse
    {
        $groups = $this->classGroupService->getGroupsByClassId($classGroupId);
        return $this->successResponse($groups, 'Groupes récupérés avec succès');
    }
}
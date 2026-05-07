<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\ClassGroupService;
use App\Modules\Inscription\Http\Requests\StoreClassGroupRequest;
use App\Modules\Inscription\Http\Requests\GetClassGroupsRequest;
use App\Modules\Inscription\Http\Requests\CreateDefaultGroupRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Class Groups",
 *     description="Gestion des groupes de classe"
 * )
 */
class ClassGroupController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ClassGroupService $classGroupService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Liste des groupes pour une classe
     */
    public function index(GetClassGroupsRequest $request): JsonResponse
    {
        $groups = $this->classGroupService->getGroups(
            $request->input('academic_year_id'),
            $request->input('department_id'),
            $request->input('study_level'),
            $request->input('cohort')
        );

        return $this->successResponse($groups, 'Groupes récupérés avec succès');
    }

    /**
     * Créer des groupes pour une classe
     */
    public function store(StoreClassGroupRequest $request): JsonResponse
    {
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


    
    /**
     * Créer un groupe unique par défaut avec tous les étudiants
     */
    public function createDefault(CreateDefaultGroupRequest $request): JsonResponse
    {
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

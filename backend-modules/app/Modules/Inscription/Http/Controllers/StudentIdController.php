<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Http\Requests\LookupStudentIdRequest;
use App\Modules\Inscription\Http\Requests\AssignStudentIdRequest;
use App\Modules\Inscription\Services\StudentIdService;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(
 *   name="Students",
 *   description="Recherche et assignation du matricule étudiant"
 * )
 */
class StudentIdController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StudentIdService $studentIdService
    ) {}

    /**
     * @OA\Post(
     *   path="/api/students/lookup-id",
     *   tags={"Students"},
     *   summary="Récupérer le matricule par identité",
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"last_name","first_names","birth_date","birth_place"},
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(property="first_names", type="string"),
     *       @OA\Property(property="birth_date", type="string", format="date"),
     *       @OA\Property(property="birth_place", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(@OA\Property(property="student_id_number", type="string"))),
     *   @OA\Response(response=404, description="Introuvable")
     * )
     */
    public function lookup(LookupStudentIdRequest $request): JsonResponse
    {
        $studentIdNumber = $this->studentIdService->lookupByIdentity($request->validated());
        
        return $this->successResponse(
            ['student_id_number' => $studentIdNumber],
            'Matricule récupéré avec succès'
        );
    }

    /**
     * @OA\Post(
     *   path="/api/students/assign-id",
     *   tags={"Students"},
     *   summary="Assigner un matricule (numéro de téléphone)",
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"last_name","first_names","birth_date","birth_place","phone"},
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(property="first_names", type="string"),
     *       @OA\Property(property="birth_date", type="string", format="date"),
     *       @OA\Property(property="birth_place", type="string"),
     *       @OA\Property(property="phone", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Créé", @OA\JsonContent(@OA\Property(property="student_id_number", type="string"))),
     *   @OA\Response(response=404, description="Identité introuvable"),
     *   @OA\Response(response=409, description="Matricule déjà défini")
     * )
     */
    public function assign(AssignStudentIdRequest $request): JsonResponse
    {
        $student = $this->studentIdService->assignStudentId($request->validated());
        
        return $this->createdResponse(
            ['student_id_number' => $student->student_id_number],
            'Matricule assigné avec succès'
        );
    }
}

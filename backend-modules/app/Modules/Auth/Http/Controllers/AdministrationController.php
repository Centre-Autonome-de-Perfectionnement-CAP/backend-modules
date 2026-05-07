<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AdministrationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Gestion des utilisateurs administratifs"
 * )
 */
class AdministrationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AdministrationService $administrationService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/auth/administration",
     *     summary="Récupérer les utilisateurs de l'administration",
     *     description="Retourne la liste des utilisateurs ayant des rôles administratifs",
     *     operationId="getAdministrationUsers",
     *     tags={"Administration"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs administratifs",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="first_name", type="string"),
     *                 @OA\Property(property="last_name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="photo", type="string"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(['role', 'search']);
        $users = $this->administrationService->getAdminUsers($filters);
        return $this->successResponse($users, 'Utilisateurs administratifs récupérés avec succès');
    }

    /**
     * Récupère les membres du soutien informatique
     *
     * @OA\Get(
     *     path="/api/auth/soutien-informatique",
     *     summary="Liste des membres du soutien informatique",
     *     tags={"Administration"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des membres du soutien informatique"
     *     )
     * )
     */
    public function soutienInformatique(Request $request)
    {
        $users = $this->administrationService->getSoutienInformatique();
        return $this->successResponse($users, 'Membres du soutien informatique récupérés avec succès');
    }
}

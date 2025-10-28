<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\AdministrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Gestion des utilisateurs administratifs"
 * )
 */
class AdministrationController extends Controller
{
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
        try {
            $filters = $request->only(['role', 'search']);
            $users = $this->administrationService->getAdminUsers($filters);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des utilisateurs administratifs',
                'message' => $e->getMessage()
            ], 500);
        }
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
        try {
            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'soutien_informatique');
            })
            ->with(['roles' => function ($query) {
                $query->select('roles.id', 'roles.name', 'roles.display_name');
            }])
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'photo')
            ->orderBy('last_name')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des membres du soutien informatique',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

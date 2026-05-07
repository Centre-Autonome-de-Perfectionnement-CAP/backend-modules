<?php

namespace App\Modules\Stockage\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Services\PermissionService;
use App\Modules\Stockage\Http\Requests\GrantPermissionRequest;
use App\Modules\Stockage\Http\Resources\FilePermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(
 *     name="File Permissions",
 *     description="Gestion des permissions sur les fichiers"
 * )
 */
class FilePermissionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PermissionService $permissionService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/files/{file}/permissions",
     *     summary="Permissions d'un fichier",
     *     description="Récupère toutes les permissions accordées sur un fichier",
     *     operationId="getFilePermissions",
     *     tags={"File Permissions"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FilePermission"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé")
     * )
     */
    public function index(File $file): JsonResponse
    {
        $this->authorize('managePermissions', $file);

        $permissions = $this->permissionService->getFilePermissions($file);

        return response()->json([
            'success' => true,
            'data' => FilePermissionResource::collection($permissions),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/permissions/grant",
     *     summary="Accorder une permission",
     *     description="Accorde une permission sur un fichier à un utilisateur ou un rôle",
     *     operationId="grantFilePermission",
     *     tags={"File Permissions"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_type"},
     *             @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur (optionnel si role_id fourni)"),
     *             @OA\Property(property="role_id", type="integer", description="ID du rôle (optionnel si user_id fourni)"),
     *             @OA\Property(property="permission_type", type="string", enum={"read", "write", "delete", "share", "admin"}),
     *             @OA\Property(property="expires_at", type="string", format="date-time", description="Date d'expiration (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission accordée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission accordée avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/FilePermission")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function grant(GrantPermissionRequest $request, File $file): JsonResponse
    {
        $this->authorize('managePermissions', $file);

        $expiresAt = $request->input('expires_at') 
                ? new \DateTime($request->input('expires_at'))
                : null;

            if ($request->input('user_id')) {
                $permission = $this->permissionService->grantUserPermission(
                    $file,
                    $request->input('user_id'),
                    $request->input('permission_type'),
                    Auth::id(),
                    $expiresAt
                );
            } else {
                $permission = $this->permissionService->grantRolePermission(
                    $file,
                    $request->input('role_id'),
                    $request->input('permission_type'),
                    Auth::id(),
                    $expiresAt
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Permission accordée avec succès.',
                'data' => new FilePermissionResource($permission),
            ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/permissions/revoke",
     *     summary="Révoquer une permission",
     *     description="Révoque une permission sur un fichier d'un utilisateur ou d'un rôle",
     *     operationId="revokeFilePermission",
     *     tags={"File Permissions"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_type"},
     *             @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur (optionnel si role_id fourni)"),
     *             @OA\Property(property="role_id", type="integer", description="ID du rôle (optionnel si user_id fourni)"),
     *             @OA\Property(property="permission_type", type="string", enum={"read", "write", "delete", "share", "admin"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission révoquée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission révoquée avec succès.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier ou permission non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function revoke(Request $request, File $file): JsonResponse
    {
        $this->authorize('managePermissions', $file);

        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'role_id' => 'nullable|integer|exists:roles,id',
            'permission_type' => 'required|in:read,write,delete,share,admin',
        ]);

        if ($request->input('user_id')) {
                $success = $this->permissionService->revokeUserPermission(
                    $file,
                    $request->input('user_id'),
                    $request->input('permission_type'),
                    Auth::id()
                );
            } else {
                $success = $this->permissionService->revokeRolePermission(
                    $file,
                    $request->input('role_id'),
                    $request->input('permission_type'),
                    Auth::id()
                );
            }

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission révoquée avec succès.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Permission non trouvée.',
            ], 404);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/permissions/check",
     *     summary="Vérifier une permission",
     *     description="Vérifie si l'utilisateur actuel a une permission spécifique sur un fichier",
     *     operationId="checkFilePermission",
     *     tags={"File Permissions"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_type"},
     *             @OA\Property(property="permission_type", type="string", enum={"read", "write", "delete", "share", "admin"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vérification effectuée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="has_permission", type="boolean"),
     *                 @OA\Property(property="permission_type", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function check(Request $request, File $file): JsonResponse
    {
        $request->validate([
            'permission_type' => 'required|in:read,write,delete,share,admin',
        ]);

        $hasPermission = $this->permissionService->userCan(
            $file,
            Auth::id(),
            $request->input('permission_type')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'has_permission' => $hasPermission,
                'permission_type' => $request->input('permission_type'),
            ],
        ]);
    }
}

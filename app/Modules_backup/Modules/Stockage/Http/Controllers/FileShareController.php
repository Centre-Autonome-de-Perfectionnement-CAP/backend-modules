<?php

namespace App\Modules\Stockage\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Models\FileShare;
use App\Modules\Stockage\Services\FileShareService;
use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Http\Requests\CreateShareRequest;
use App\Modules\Stockage\Http\Resources\FileShareResource;
use App\Modules\Stockage\Http\Resources\FileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(
 *     name="File Sharing",
 *     description="Gestion du partage de fichiers"
 * )
 */
class FileShareController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FileShareService $shareService,
        protected FileStorageService $storageService
    ) {
        $this->middleware('auth:sanctum')->except(['access', 'download']);
    }

    /**
     * @OA\Get(
     *     path="/api/files/{file}/shares",
     *     summary="Partages d'un fichier",
     *     description="Récupère tous les liens de partage d'un fichier",
     *     operationId="getFileShares",
     *     tags={"File Sharing"},
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
     *         description="Partages récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FileShare"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé")
     * )
     */
    public function index(File $file): JsonResponse
    {
        $this->authorize('share', $file);

        $shares = $this->shareService->getFileShares($file);

        return response()->json([
            'success' => true,
            'data' => FileShareResource::collection($shares),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/shares",
     *     summary="Créer un partage",
     *     description="Crée un nouveau lien de partage pour un fichier",
     *     operationId="createFileShare",
     *     tags={"File Sharing"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", description="Mot de passe pour accéder au partage"),
     *             @OA\Property(property="allow_download", type="boolean", default=true, description="Autoriser le téléchargement"),
     *             @OA\Property(property="allow_preview", type="boolean", default=true, description="Autoriser l'aperçu"),
     *             @OA\Property(property="max_downloads", type="integer", description="Nombre maximum de téléchargements"),
     *             @OA\Property(property="expires_at", type="string", format="date-time", description="Date d'expiration")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Partage créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lien de partage créé avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/FileShare")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(CreateShareRequest $request, File $file): JsonResponse
    {
        $this->authorize('share', $file);

        $options = [
                'password' => $request->input('password'),
                'allow_download' => $request->input('allow_download', true),
                'allow_preview' => $request->input('allow_preview', true),
                'max_downloads' => $request->input('max_downloads'),
                'expires_at' => $request->input('expires_at') 
                    ? new \DateTime($request->input('expires_at'))
                    : null,
            ];

            $share = $this->shareService->createShare($file, Auth::id(), $options);

            return response()->json([
                'success' => true,
                'message' => 'Lien de partage créé avec succès.',
                'data' => new FileShareResource($share),
            ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/files/{file}/shares/{share}",
     *     summary="Détails d'un partage",
     *     description="Affiche les détails d'un lien de partage spécifique",
     *     operationId="getFileShare",
     *     tags={"File Sharing"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="share",
     *         in="path",
     *         description="ID du partage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du partage récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/FileShare")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier ou partage non trouvé")
     * )
     */
    public function show(File $file, FileShare $share): JsonResponse
    {
        $this->authorize('share', $file);

        if ($share->file_id !== $file->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce partage n\'appartient pas à ce fichier.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new FileShareResource($share),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/shares/{share}/deactivate",
     *     summary="Désactiver un partage",
     *     description="Désactive un lien de partage existant",
     *     operationId="deactivateFileShare",
     *     tags={"File Sharing"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="share",
     *         in="path",
     *         description="ID du partage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partage désactivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Partage désactivé avec succès.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier ou partage non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function deactivate(File $file, FileShare $share): JsonResponse
    {
        $this->authorize('share', $file);

        if ($share->file_id !== $file->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce partage n\'appartient pas à ce fichier.',
            ], 403);
        }

        $share = $this->shareService->deactivateShare($share, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Partage désactivé avec succès.',
                'data' => new FileShareResource($share),
            ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/files/{file}/shares/{share}",
     *     summary="Supprimer un partage",
     *     description="Supprime définitivement un lien de partage",
     *     operationId="deleteFileShare",
     *     tags={"File Sharing"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="ID du fichier",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="share",
     *         in="path",
     *         description="ID du partage",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partage supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Partage supprimé avec succès.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier ou partage non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(File $file, FileShare $share): JsonResponse
    {
        $this->authorize('share', $file);

        if ($share->file_id !== $file->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce partage n\'appartient pas à ce fichier.',
            ], 403);
        }

        $this->shareService->deleteShare($share, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Partage supprimé avec succès.',
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/files/share/{token}",
     *     summary="Accéder à un fichier partagé",
     *     description="Accède aux informations d'un fichier partagé via son token (public)",
     *     operationId="accessSharedFile",
     *     tags={"File Sharing"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de partage",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", description="Mot de passe si requis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Accès autorisé au fichier partagé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="file", ref="#/components/schemas/File"),
     *                 @OA\Property(property="share", ref="#/components/schemas/FileShare")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé (mot de passe incorrect, expiré, etc.)"),
     *     @OA\Response(response=404, description="Lien de partage introuvable")
     * )
     */
    public function access(Request $request, string $token): JsonResponse
    {
        $share = $this->shareService->getShareByToken($token);

        if (!$share) {
            return response()->json([
                'success' => false,
                'message' => 'Lien de partage introuvable.',
            ], 404);
        }

        $result = $this->shareService->accessSharedFile(
                $share,
                $request->input('password'),
                false
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'file' => new FileResource($result['file']),
                    'share' => new FileShareResource($result['share']),
                ],
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/files/share/{token}/download",
     *     summary="Télécharger un fichier partagé",
     *     description="Télécharge un fichier partagé via son token (public)",
     *     operationId="downloadSharedFile",
     *     tags={"File Sharing"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de partage",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", description="Mot de passe si requis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier téléchargé avec succès",
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *     @OA\Response(response=403, description="Accès refusé (mot de passe incorrect, expiré, etc.)"),
     *     @OA\Response(response=404, description="Lien de partage introuvable")
     * )
     */
    public function download(Request $request, string $token)
    {
        $share = $this->shareService->getShareByToken($token);

        if (!$share) {
            return response()->json([
                'success' => false,
                'message' => 'Lien de partage introuvable.',
            ], 404);
        }

        $result = $this->shareService->accessSharedFile(
                $share,
                $request->input('password'),
                true
            );

            $file = $result['file'];
            $download = $this->storageService->downloadFile($file, null);

            return response()->stream(
                function () use ($download) {
                    echo $download['stream'];
                },
                200,
                [
                    'Content-Type' => $download['mimeType'],
                    'Content-Disposition' => 'attachment; filename="' . $download['filename'] . '"',
                ]
            );
    }
}

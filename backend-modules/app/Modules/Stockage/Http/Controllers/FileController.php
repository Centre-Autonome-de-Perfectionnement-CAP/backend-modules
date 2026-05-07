<?php

namespace App\Modules\Stockage\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Services\PermissionService;
use App\Modules\Stockage\Http\Requests\UploadFileRequest;
use App\Modules\Stockage\Http\Requests\UpdateFileRequest;
use App\Modules\Stockage\Http\Resources\FileResource;
use App\Modules\Stockage\Http\Resources\FileActivityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(
 *     name="File Management",
 *     description="Gestion des fichiers et stockage"
 * )
 */
class FileController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FileStorageService $storageService,
        protected PermissionService $permissionService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/files",
     *     summary="Liste des fichiers",
     *     description="Récupère la liste des fichiers accessibles par l'utilisateur avec possibilité de filtrage",
     *     operationId="getFiles",
     *     tags={"File Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="collection",
     *         in="query",
     *         description="Filtrer par collection",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="visibility",
     *         in="query",
     *         description="Filtrer par visibilité",
     *         required=false,
     *         @OA\Schema(type="string", enum={"public", "private"})
     *     ),
     *     @OA\Parameter(
     *         name="module_name",
     *         in="query",
     *         description="Filtrer par module",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par nom",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichiers récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/File")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['collection', 'visibility', 'module_name', 'search']);
        
        $files = $this->permissionService->getAccessibleFiles(Auth::id(), $filters);

        return response()->json([
            'success' => true,
            'data' => FileResource::collection($files),
            'meta' => [
                'total' => $files->count(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/files",
     *     summary="Uploader un fichier",
     *     description="Upload un nouveau fichier avec gestion des métadonnées et permissions",
     *     operationId="uploadFile",
     *     tags={"File Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Le fichier à uploader"
     *                 ),
     *                 @OA\Property(property="visibility", type="string", enum={"public", "private"}, default="private"),
     *                 @OA\Property(property="collection", type="string", default="default"),
     *                 @OA\Property(property="module_name", type="string"),
     *                 @OA\Property(property="module_resource_type", type="string"),
     *                 @OA\Property(property="module_resource_id", type="integer"),
     *                 @OA\Property(property="metadata", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fichier uploadé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fichier uploadé avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/File")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $file = $this->storageService->uploadFile(
                uploadedFile: $request->file('file'),
                userId: Auth::id(),
                visibility: $request->input('visibility', 'private'),
                collection: $request->input('collection', 'default'),
                moduleName: $request->input('module_name'),
                moduleResourceType: $request->input('module_resource_type'),
                moduleResourceId: $request->input('module_resource_id'),
                metadata: $request->input('metadata', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Fichier uploadé avec succès.',
                'data' => new FileResource($file),
            ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/files/{file}",
     *     summary="Détails d'un fichier",
     *     description="Récupère les détails d'un fichier spécifique",
     *     operationId="getFile",
     *     tags={"File Management"},
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
     *         description="Détails du fichier récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/File")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé")
     * )
     */
    public function show(File $file): JsonResponse
    {
        $this->authorize('view', $file);

        return response()->json([
            'success' => true,
            'data' => new FileResource($file),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/files/{file}",
     *     summary="Mettre à jour un fichier",
     *     description="Met à jour les métadonnées ou la collection d'un fichier",
     *     operationId="updateFile",
     *     tags={"File Management"},
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
     *             @OA\Property(property="collection", type="string"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fichier mis à jour avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/File")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function update(UpdateFileRequest $request, File $file): JsonResponse
    {
        $this->authorize('update', $file);

        if ($request->has('collection')) {
                $file = $this->storageService->moveToCollection(
                    $file,
                    $request->input('collection'),
                    Auth::id()
                );
            }

            if ($request->has('metadata')) {
                $file->update(['metadata' => $request->input('metadata')]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fichier mis à jour avec succès.',
                'data' => new FileResource($file->fresh()),
            ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/files/{file}",
     *     summary="Supprimer un fichier",
     *     description="Supprime un fichier (soft delete)",
     *     operationId="deleteFile",
     *     tags={"File Management"},
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
     *         description="Fichier supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fichier supprimé avec succès.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(File $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $this->storageService->deleteFile($file, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprimé avec succès.',
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/files/{file}/download",
     *     summary="Télécharger un fichier",
     *     description="Télécharge un fichier spécifique",
     *     operationId="downloadFile",
     *     tags={"File Management"},
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
     *         description="Fichier téléchargé avec succès",
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function download(File $file)
    {
        $this->authorize('download', $file);

        $download = $this->storageService->downloadFile($file, Auth::id());

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

    public function view(File $file)
    {
        $this->authorize('view', $file);

        $download = $this->storageService->downloadFile($file, Auth::id());

        return response()->stream(
            function () use ($download) {
                echo $download['stream'];
            },
            200,
            [
                'Content-Type' => $download['mimeType'],
                'Content-Disposition' => 'inline; filename="' . $download['filename'] . '"',
            ]
        );
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/visibility",
     *     summary="Changer la visibilité d'un fichier",
     *     description="Change la visibilité d'un fichier (public/privé)",
     *     operationId="changeFileVisibility",
     *     tags={"File Management"},
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
     *             required={"visibility"},
     *             @OA\Property(property="visibility", type="string", enum={"public", "private"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Visibilité modifiée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Visibilité modifiée avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/File")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function changeVisibility(Request $request, File $file): JsonResponse
    {
        $this->authorize('changeVisibility', $file);

        $request->validate([
            'visibility' => 'required|in:public,private',
        ]);

        $file = $this->storageService->changeVisibility(
                $file,
                $request->input('visibility'),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Visibilité modifiée avec succès.',
                'data' => new FileResource($file),
            ]);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/lock",
     *     summary="Verrouiller un fichier",
     *     description="Verrouille un fichier pour empêcher les modifications",
     *     operationId="lockFile",
     *     tags={"File Management"},
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
     *         description="Fichier verrouillé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fichier verrouillé avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/File")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function lock(File $file): JsonResponse
    {
        $this->authorize('lock', $file);

        $file = $this->storageService->lockFile($file, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Fichier verrouillé avec succès.',
                'data' => new FileResource($file),
            ]);
    }

    /**
     * @OA\Post(
     *     path="/api/files/{file}/unlock",
     *     summary="Déverrouiller un fichier",
     *     description="Déverrouille un fichier pour permettre les modifications",
     *     operationId="unlockFile",
     *     tags={"File Management"},
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
     *         description="Fichier déverrouillé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fichier déverrouillé avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/File")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function unlock(File $file): JsonResponse
    {
        $this->authorize('lock', $file);

        $file = $this->storageService->unlockFile($file, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Fichier déverrouillé avec succès.',
                'data' => new FileResource($file),
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/files/{file}/activities",
     *     summary="Historique des activités d'un fichier",
     *     description="Récupère l'historique des activités (logs) d'un fichier",
     *     operationId="getFileActivities",
     *     tags={"File Management"},
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
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FileActivity"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé")
     * )
     */
    public function activities(File $file): JsonResponse
    {
        $this->authorize('view', $file);

        $activities = $file->activities()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => FileActivityResource::collection($activities),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/files/public",
     *     summary="Fichiers publics",
     *     description="Récupère la liste des fichiers publics avec possibilité de filtrage",
     *     operationId="getPublicFiles",
     *     tags={"File Management"},
     *     @OA\Parameter(
     *         name="collection",
     *         in="query",
     *         description="Filtrer par collection",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="module_name",
     *         in="query",
     *         description="Filtrer par module",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par nom",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichiers publics récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/File")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function publicFiles(Request $request): JsonResponse
    {
        $filters = $request->only(['collection', 'module_name', 'search']);
        
        $files = $this->storageService->getPublicFiles($filters);

        return response()->json([
            'success' => true,
            'data' => FileResource::collection($files),
            'meta' => [
                'total' => $files->count(),
            ],
        ]);
    }
}

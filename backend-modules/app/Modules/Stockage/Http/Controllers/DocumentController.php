<?php

namespace App\Modules\Stockage\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(
 *     name="Documents",
 *     description="Gestion des documents (administratif, pédagogique, légal, organisation)"
 * )
 */
class DocumentController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/documents",
     *     summary="Liste des documents officiels",
     *     tags={"Documents"},
     *     @OA\Parameter(name="categorie", in="query", required=false, @OA\Schema(type="string", enum={"administratif","pedagogique","legal","organisation"})),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="titre", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string", enum={"pdf","doc","xls","ppt","lien"}),
     *                 @OA\Property(property="taille", type="string"),
     *                 @OA\Property(property="datePublication", type="string", format="date"),
     *                 @OA\Property(property="lien", type="string"),
     *                 @OA\Property(property="categorie", type="string", enum={"administratif","pedagogique","legal","organisation"})
     *             ))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = File::officialDocuments()
            ->orderBy('date_publication', 'desc');

        if ($request->has('categorie')) {
            $query->byCategorie($request->query('categorie'));
        }

        $documents = $query->get()->map(function ($file) {
            // Déterminer le type basé sur l'extension
            $typeMap = [
                'pdf' => 'pdf',
                'doc' => 'doc', 'docx' => 'doc',
                'xls' => 'xls', 'xlsx' => 'xls',
                'ppt' => 'ppt', 'pptx' => 'ppt',
            ];
            $type = $typeMap[$file->extension] ?? 'lien';

            return [
                'id' => $file->id,
                'titre' => $file->original_name,
                'description' => $file->description ?? '',
                'type' => $type,
                'taille' => $file->size_for_humans,
                'datePublication' => $file->date_publication?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'lien' => $file->url,
                'categorie' => $file->document_categorie,
            ];
        });

        return $this->successResponse($documents, 'Données récupérées avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/documents/{document}",
     *     summary="Détails d'un document",
     *     tags={"Documents"},
     *     @OA\Parameter(name="document", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Document introuvable")
     * )
     */
    public function show(File $file): JsonResponse
    {
        if (!$file->is_official_document) {
            return response()->json(['message' => 'Ce fichier n\'est pas un document officiel'], 404);
        }

        $typeMap = [
            'pdf' => 'pdf',
            'doc' => 'doc', 'docx' => 'doc',
            'xls' => 'xls', 'xlsx' => 'xls',
            'ppt' => 'ppt', 'pptx' => 'ppt',
        ];
        $type = $typeMap[$file->extension] ?? 'lien';

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $file->id,
                'titre' => $file->original_name,
                'description' => $file->description ?? '',
                'type' => $type,
                'taille' => $file->size_for_humans,
                'datePublication' => $file->date_publication?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'lien' => $file->url,
                'categorie' => $file->document_categorie,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/documents",
     *     summary="Créer un document officiel",
     *     description="Upload fichier OU lien externe",
     *     tags={"Documents"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"titre","description","datePublication","categorie"},
     *                 @OA\Property(property="titre", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary", description="Fichier (requis si pas de lien)"),
     *                 @OA\Property(property="lien", type="string", format="url", description="URL (requis si pas de fichier)"),
     *                 @OA\Property(property="datePublication", type="string", format="date"),
     *                 @OA\Property(property="categorie", type="string", enum={"administratif","pedagogique","legal","organisation"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Créé", 
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="titre", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="taille", type="string"),
     *                 @OA\Property(property="datePublication", type="string"),
     *                 @OA\Property(property="lien", type="string"),
     *                 @OA\Property(property="categorie", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Validation échouée")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'file' => ['required_without:lien', 'file', 'max:10240'],
            'lien' => ['required_without:file', 'url'],
            'datePublication' => ['required', 'date'],
            'categorie' => ['required', 'in:administratif,pedagogique,legal,organisation'],
        ]);

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $extension = $uploadedFile->getClientOriginalExtension();
            $filename = uniqid() . '.' . $extension;
            $path = $uploadedFile->storeAs('documents', $filename, 'public');

            $file = File::create([
                'user_id' => auth()->id(),
                'name' => $filename,
                'original_name' => $data['titre'],
                'description' => $data['description'],
                'path' => $path,
                'disk' => 'public',
                'visibility' => 'public',
                'size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'extension' => $extension,
                'is_official_document' => true,
                'document_categorie' => $data['categorie'],
                'date_publication' => $data['datePublication'],
            ]);
        } else {
            $file = File::create([
                'user_id' => auth()->id(),
                'name' => $data['titre'],
                'original_name' => $data['titre'],
                'description' => $data['description'],
                'path' => $data['lien'],
                'disk' => 'url',
                'visibility' => 'public',
                'extension' => 'url',
                'is_official_document' => true,
                'document_categorie' => $data['categorie'],
                'date_publication' => $data['datePublication'],
            ]);
        }

        $typeMap = ['pdf' => 'pdf', 'doc' => 'doc', 'docx' => 'doc', 'xls' => 'xls', 'xlsx' => 'xls', 'ppt' => 'ppt', 'pptx' => 'ppt'];
        $type = $typeMap[$file->extension] ?? 'lien';

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $file->id,
                'titre' => $file->original_name,
                'description' => $file->description,
                'type' => $type,
                'taille' => $file->size_for_humans,
                'datePublication' => $file->date_publication->format('Y-m-d'),
                'lien' => $file->url,
                'categorie' => $file->document_categorie,
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/documents/{document}",
     *     summary="Mettre à jour un document",
     *     tags={"Documents"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="document", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent()),
     *     @OA\Response(response=200, description="Mis à jour")
     * )
     */
    public function update(Request $request, File $file): JsonResponse
    {
        if (!$file->is_official_document) {
            return response()->json(['message' => 'Ce fichier n\'est pas un document officiel'], 404);
        }

        $data = $request->validate([
            'titre' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'datePublication' => ['sometimes', 'date'],
            'categorie' => ['sometimes', 'in:administratif,pedagogique,legal,organisation'],
        ]);

        if (isset($data['titre'])) $file->original_name = $data['titre'];
        if (isset($data['description'])) $file->description = $data['description'];
        if (isset($data['datePublication'])) $file->date_publication = $data['datePublication'];
        if (isset($data['categorie'])) $file->document_categorie = $data['categorie'];

        $file->save();

        $typeMap = ['pdf' => 'pdf', 'doc' => 'doc', 'docx' => 'doc', 'xls' => 'xls', 'xlsx' => 'xls', 'ppt' => 'ppt', 'pptx' => 'ppt'];
        $type = $typeMap[$file->extension] ?? 'lien';

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $file->id,
                'titre' => $file->original_name,
                'description' => $file->description,
                'type' => $type,
                'taille' => $file->size_for_humans,
                'datePublication' => $file->date_publication->format('Y-m-d'),
                'lien' => $file->url,
                'categorie' => $file->document_categorie,
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/documents/{document}",
     *     summary="Supprimer un document",
     *     tags={"Documents"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="document", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Supprimé")
     * )
     */
    public function destroy(File $file): JsonResponse
    {
        if (!$file->is_official_document) {
            return response()->json(['message' => 'Ce fichier n\'est pas un document officiel'], 404);
        }

        // Supprimer le fichier physique si ce n'est pas un lien
        if ($file->disk !== 'url' && Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $file->delete();
        return response()->json(['success' => true]);
    }
}

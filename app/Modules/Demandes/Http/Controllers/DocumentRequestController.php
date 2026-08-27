<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Http\Resources\DocumentRequestListResource;
use App\Modules\Demandes\Models\DocumentRequest;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use App\Modules\Demandes\Services\DocumentStorageService;
use App\Modules\Demandes\Services\SecretaryFileService;
use App\Modules\Demandes\WorkflowConstants;
use App\Exceptions\BusinessException;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Storage};

/**
 * CORRECTIF (v2) — B3.1 + B3.2, basé sur le DocumentRequestController réel
 *
 * - index() : INCHANGÉ — retourne toujours la collection brute (pas de
 *   Resource), pour ne rien casser côté frontend qui consomme déjà ce
 *   format exact.
 * - indexPaginated() : NOUVEAU endpoint additif (B3.2), qui lui utilise
 *   DocumentRequestListResource pour un format stable et documenté.
 * - Tout le reste (show, previewFile, fichiers secrétaire) reproduit
 *   fidèlement le contrôleur réel, avec la même extraction de
 *   SecretaryFileService que dans B1.
 */
class DocumentRequestController extends Controller
{
    use ApiResponse;

    private const FILE_LABELS = [
        'demande_manuscrite'       => 'Demande manuscrite',
        'acte_naissance'           => 'Acte de naissance',
        'attestation_succes_file'  => 'Attestation de succes',
        'quittance'                => 'Quittance',
        'quittance_online'         => 'Quittance en ligne',
        'recu_paiement'            => 'Recu de paiement',
        'bulletin'                 => 'Bulletin de notes',
    ];

    public function __construct(
        protected DocumentRequestQueryService $queryService,
        protected DocumentStorageService      $storageService,
        protected SecretaryFileService        $secretaryFileService,
        protected \App\Modules\Demandes\Services\ContactDemandeurService $contactDemandeurService,
    ) {}

    // ── Listing (INCHANGÉ — format brut conservé) ─────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user    = Auth::user();
        $role    = WorkflowConstants::canonicalRole($user->roles->first()?->slug ?? null);
        $filters = $request->only(['status', 'type', 'search']);

        $demandes = $this->queryService->listing($role, $user, $filters);

        return response()->json([
            'success' => true,
            'data'    => $demandes,
            'role'    => $role,
        ]);
    }

    // ── AJOUT (B3.2) — Listing paginé ──────────────────────────────────────────

    public function indexPaginated(Request $request): JsonResponse
    {
        $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $user    = Auth::user();
        $role    = WorkflowConstants::canonicalRole($user->roles->first()?->slug ?? null);
        $filters = $request->only(['status', 'type', 'search']);
        $perPage = (int) $request->input('per_page', 25);

        $paginator = $this->queryService->paginatedListing($role, $user, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data'    => DocumentRequestListResource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'role' => $role,
        ]);
    }

    // ── Détail (INCHANGÉ) ──────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        try {
            $demande = $this->queryService->findOrFail($id);

            $resolved = $this->storageService->resolveDocuments(
                $demande->files, $demande->complement_files
            );

            $result                      = (array) $demande;
            $result['documents_resolus'] = $resolved;

            return $this->successResponse((object) $result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    // ── Aperçu / téléchargement (INCHANGÉ) ─────────────────────────────────────

    public function previewFile(Request $request, int $id, string $source, string $key)
    {
        try {
            $demande = $this->queryService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }

        [$path, $displayName, $error] = $this->resolveFilePath($demande, $source, $key);

        if ($error === 'invalid_source') {
            return $this->errorResponse('Source de fichier invalide. Valeurs : initial, complement, active, secretary.', 422);
        }
        if ($error === 'not_found') {
            return $this->notFoundResponse('Fichier introuvable.');
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return $this->notFoundResponse('Fichier introuvable sur le serveur.');
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        return Storage::disk('public')->response($path, $displayName, [], $disposition);
    }

    // ── Fichiers secrétaire (extraits vers SecretaryFileService — B1.1) ───────

    public function uploadSecretaryFiles(Request $request, int $id): JsonResponse
    {
        $demande = DocumentRequest::findOrFail($id);
        $this->authorize('manageSecretaryFiles', $demande);

        $request->validate([
            'files'           => 'required|array|min:1',
            'files.*.file'    => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'files.*.name'    => 'required|string|max:200',
            'files.*.comment' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->secretaryFileService->upload($demande, $request->input('files', []), $request);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse(array_merge(
            ['message' => 'Fichiers ajoutés avec succès.'], $result
        ));
    }

    public function updateSecretaryFileComment(Request $request, int $id, string $fileId): JsonResponse
    {
        $demande = DocumentRequest::findOrFail($id);
        $this->authorize('manageSecretaryFiles', $demande);
        $request->validate(['comment' => 'nullable|string|max:1000']);

        try {
            $secretaryFiles = $this->secretaryFileService->updateComment($demande, $fileId, $request->input('comment'));
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse([
            'message'         => 'Commentaire mis à jour.',
            'secretary_files' => $secretaryFiles,
        ]);
    }

    public function deleteSecretaryFile(int $id, string $fileId): JsonResponse
    {
        $demande = DocumentRequest::findOrFail($id);
        $this->authorize('manageSecretaryFiles', $demande);

        try {
            $secretaryFiles = $this->secretaryFileService->delete($demande, $fileId);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse([
            'message'         => 'Fichier supprimé.',
            'secretary_files' => $secretaryFiles,
        ]);
    }

    // ── AJOUT : message libre secrétaire → demandeur (WhatsApp + email) ──────────

    public function contactDemandeur(Request $request, int $id): JsonResponse
    {
        $demande = DocumentRequest::findOrFail($id);
        // Réutilise la policy existante : secrétaire/admin uniquement,
        // même règle que pour la gestion des fichiers secrétaire.
        $this->authorize('manageSecretaryFiles', $demande);

        $request->validate([
            'message'        => 'required|string|max:3000',
            'attachments'    => 'nullable|array|max:5',
            'attachments.*'  => 'file|max:10240', // 10 Mo par pièce jointe
        ]);

        try {
            $result = $this->contactDemandeurService->send(
                $demande,
                $request->input('message'),
                $request->file('attachments', []),
                $request,
            );
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse($result, 'Envoi traité.');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS (INCHANGÉ)
    // ══════════════════════════════════════════════════════════════════════════

    private function resolveFilePath(object $demande, string $source, string $key): array
    {
        return match ($source) {
            'initial' => (function () use ($demande, $key) {
                $decoded = $this->decodeJson($demande->files);
                if (!array_key_exists($key, $decoded)) return [null, null, 'not_found'];
                return [$decoded[$key], $this->buildDisplayName($key, $decoded[$key]), null];
            })(),

            'complement' => (function () use ($demande, $key) {
                $decoded = $this->decodeJson($demande->complement_files);
                if (!array_key_exists($key, $decoded)) return [null, null, 'not_found'];
                return [$decoded[$key], $this->buildDisplayName($key, $decoded[$key]), null];
            })(),

            'active' => (function () use ($demande, $key) {
                $resolved = $this->storageService->resolveActiveFile($demande->files, $demande->complement_files, $key);
                if (!$resolved['path']) return [null, null, 'not_found'];
                return [$resolved['path'], $this->buildDisplayName($key, $resolved['path']), null];
            })(),

            'secretary' => (function () use ($demande, $key) {
                $decoded = $this->decodeJson($demande->secretary_files);
                foreach ($decoded as $file) {
                    if (($file['id'] ?? '') === $key) {
                        $path = $file['path'] ?? null;
                        return [$path, $file['original_name'] ?? basename((string) $path), null];
                    }
                }
                return [null, null, 'not_found'];
            })(),

            default => [null, null, 'invalid_source'],
        };
    }

    private function decodeJson(mixed $raw): array
    {
        if (is_array($raw))  return $raw;
        if (is_string($raw)) return json_decode($raw, true) ?: [];
        return [];
    }

    private function buildDisplayName(string $key, ?string $path): string
    {
        $ext = $path ? (pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf') : 'pdf';
        return (self::FILE_LABELS[$key] ?? $key) . '.' . $ext;
    }
}

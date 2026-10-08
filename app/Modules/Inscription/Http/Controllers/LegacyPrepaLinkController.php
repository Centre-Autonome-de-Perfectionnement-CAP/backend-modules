<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Inscription\Services\LegacyPrepaLinkService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyPrepaLinkController extends Controller
{
    use ApiResponse, HasPagination;

    // Module réservé à l'administrateur uniquement — doit rester synchronisé
    // avec ALLOWED_ROLES côté frontend (src/views/pages/legacy-prepa/LegacyPrepaGuard.tsx)
    private const ALLOWED_ROLES = ['admin', 'chef-cap'];

    public function __construct(
        protected LegacyPrepaLinkService $service
    ) {
        $this->middleware('auth:sanctum');
    }

    private function isAllowed(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        $slugs = $user->roles()->pluck('slug')->all();

        return ! empty(array_intersect($slugs, self::ALLOWED_ROLES));
    }

    /**
     * @OA\Get(
     *     path="/api/inscription/legacy-prepa/candidates",
     *     tags={"LegacyPrepaLink"},
     *     summary="Liste des candidats Prépa en cours à rattacher",
     *     security={{"sanctum": {}}},
     * )
     */
    public function candidates(Request $request): JsonResponse
    {
        if (! $this->isAllowed($request->user())) {
            return $this->errorResponse('Accès réservé à l\'administrateur', 403);
        }
        $filters = $request->only(['search', 'academic_year_id', 'department_id']);
        $perPage = $this->getPerPage($request);
        $results = $this->service->searchCandidates($filters, $perPage);

        return $this->successResponse($results, 'Candidats récupérés avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/inscription/legacy-prepa/check/{pendingStudentId}",
     *     tags={"LegacyPrepaLink"},
     *     summary="Vérifie si le dossier est déjà rattaché à un historique Prépa",
     *     security={{"sanctum": {}}},
     * )
     */
    public function check(int $pendingStudentId): JsonResponse
    {
        if (! $this->isAllowed(request()->user())) {
            return $this->errorResponse('Accès réservé à l\'administrateur', 403);
        }
        $status = $this->service->checkLinkStatus($pendingStudentId);

        return $this->successResponse($status, 'Statut de rattachement récupéré');
    }

    /**
     * @OA\Post(
     *     path="/api/inscription/legacy-prepa/link/{pendingStudentId}",
     *     tags={"LegacyPrepaLink"},
     *     summary="Rattache l'historique Prépa validé au dossier courant",
     *     security={{"sanctum": {}}},
     * )
     */
    public function link(Request $request, int $pendingStudentId): JsonResponse
    {
        if (! $this->isAllowed($request->user())) {
            return $this->errorResponse('Accès réservé à l\'administrateur', 403);
        }
        $validated = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'department_id' => 'sometimes|integer|exists:departments,id',
            'student_id_number' => 'sometimes|string|max:50',
            'study_level' => 'sometimes|string|max:50',
            'year_decision' => 'sometimes|in:pass,fail,repeat',
            'deliberation_date' => 'sometimes|nullable|date',
            'role_id' => 'sometimes|nullable|integer',
            'financial_status' => 'sometimes|in:Exonéré,Non exonéré',
            'cohort' => 'sometimes|nullable|string|max:100',
        ]);

        $result = $this->service->linkLegacyPrepa($pendingStudentId, $validated);

        return $this->successResponse($result, 'Historique Prépa rattaché avec succès. L\'étudiant est désormais éligible à l\'inscription Spécialité.');
    }
}

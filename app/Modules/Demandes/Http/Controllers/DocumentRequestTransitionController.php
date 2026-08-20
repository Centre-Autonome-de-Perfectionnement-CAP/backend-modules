<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\Http\Requests\TransitionRequest;
use App\Modules\Demandes\Services\TransitionService;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Applique une transition de workflow sur une demande.
 *
 * AJOUTS vs original :
 *
 * 1. Protection double-clic (idempotence)
 *    Si le même utilisateur soumet la même action sur le même dossier
 *    en moins de 5s, la deuxième requête reçoit un 200 avec le résultat
 *    de la première (pas un 409, pas une erreur — c'est transparent).
 *    Clé de cache : "transition_lock:{user_id}:{demande_id}:{action}"
 *    TTL : 5 secondes.
 *
 * 2. Invalidation badge-count
 *    Après chaque transition réussie, le cache badge du rôle actuel
 *    et du rôle suivant est invalidé pour que tous les acteurs voient
 *    le bon compteur immédiatement.
 *
 * Logique métier : IDENTIQUE à l'original (TransitionService::apply() inchangé).
 */
class DocumentRequestTransitionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TransitionService $transitionService,
    ) {}

    public function __invoke(TransitionRequest $request, int $id): JsonResponse
    {
        $user    = Auth::user();
        $role    = WorkflowConstants::canonicalRole($user->roles->first()?->slug ?? null);
        $action  = $request->validated()['action'];
        $payload = $request->validated();

        // ── Protection double-clic ─────────────────────────────────────────────
        // Clé unique : utilisateur + dossier + action
        $lockKey = "transition_lock:{$user->id}:{$id}:{$action}";

        // Si la même requête a déjà été traitée dans les 5 dernières secondes,
        // on retourne le résultat mis en cache (transparent pour le frontend).
        if (Cache::has($lockKey)) {
            $cached = Cache::get($lockKey);
            return $this->successResponse($cached, 'Statut mis à jour avec succès.');
        }

        try {
            $updated = $this->transitionService->apply($id, $action, $payload, $role);

            // Mettre en cache le résultat 5s (protection double-clic)
            Cache::put($lockKey, $updated, 5);

            // ── Invalidation badge-count ───────────────────────────────────────
            // Rôle actuel (son badge diminue)
            Cache::forget('badge_' . $role);

            // Rôle suivant (son badge augmente)
            $nextRole = WorkflowConstants::STATUS_TO_ROLE[$updated->status] ?? null;
            if ($nextRole) {
                Cache::forget('badge_' . $nextRole);
                // Pour Responsable Division : invalider les deux types
                if ($nextRole === 'responsable-division') {
                    Cache::forget('badge_' . $nextRole . '_formation_continue');
                    Cache::forget('badge_' . $nextRole . '_formation_distance');
                }
            }

            // Secrétaire a une vue globale → toujours invalider
            Cache::forget('badge_secretaire');

            return $this->successResponse($updated, 'Statut mis à jour avec succès.');

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Transition error', [
                'demande_id' => $id,
                'action'     => $action ?? 'unknown',
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la transition.',
            ], 500);
        }
    }
}

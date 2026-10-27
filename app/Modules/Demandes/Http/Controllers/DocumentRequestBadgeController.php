<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Badge-count avec cache 30s.
 *
 * AVANT : COUNT(*) SQL à chaque poll frontend.
 *         60 VUs × poll toutes les 5s = 720 COUNT/min sur MySQL.
 *
 * APRÈS : Cache::remember(30s) par clé (rôle + type division).
 *         Quel que soit le nombre de VUs, 1 COUNT toutes les 30s par rôle.
 *         Cache invalidé automatiquement dans DocumentRequestTransitionController
 *         après chaque transition.
 *
 * Toute la logique countForRole() est IDENTIQUE à l'original.
 */
class DocumentRequestBadgeController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $user = Auth::user();
        $role = WorkflowConstants::canonicalRole($user->roles->first()?->slug ?? null);

        if (!$role || !array_key_exists($role, WorkflowConstants::VISIBLE_STATUSES)) {
            return $this->successResponse(['count' => 0]);
        }

        // Clé de cache unique par rôle (+ type division pour Responsable Division)
        $cacheKey = 'badge_' . $role;
        if ($role === 'responsable-division' && !empty($user->chef_division_type)) {
            $cacheKey .= '_' . $user->chef_division_type;
        }

        $count = Cache::remember($cacheKey, 30, fn () => $this->countForRole($role, $user));

        return $this->successResponse(['count' => $count]);
    }

    // ── INCHANGÉ vs original ───────────────────────────────────────────────────

    private function countForRole(string $role, object $user): int
    {
        if ($role === 'secretaire') {
            return (int) DB::table('document_requests')
                ->whereIn('status', ['submitted', 'secretary_correction', 'secretary_final_review'])
                ->count();
        }

        $statuses = WorkflowConstants::VISIBLE_STATUSES[$role] ?? [];

        if (empty($statuses)) {
            return 0;
        }

        $actionableStatuses = array_filter($statuses, fn ($s) => !in_array($s, [
            'ready_for_pickup', 'picked_up', 'rejected',
        ]));

        if (empty($actionableStatuses)) {
            return 0;
        }

        $query = DB::table('document_requests')
            ->whereIn('status', array_values($actionableStatuses));

        if ($role === 'responsable-division' && !empty($user->chef_division_type)) {
            $query->where('responsable_division_type', $user->chef_division_type);
        }

        return (int) $query->count();
    }
}

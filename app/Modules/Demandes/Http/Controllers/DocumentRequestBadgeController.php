<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Badge de notification — compteur de dossiers en attente pour l'acteur connecté.
 *
 * GET /api/attestations/document-requests/badge-count
 *
 * Répond uniquement au rôle authentifié, sans paramètre.
 * Ultra-léger : une seule requête COUNT par appel.
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

        $count = $this->countForRole($role, $user);

        return $this->successResponse(['count' => $count]);
    }

    private function countForRole(string $role, object $user): int
    {
        // ── Secrétaire : cas spécial — deux groupes de statuts ───────────────
        //
        // 'submitted'              → nouvelles demandes à valider
        // 'secretary_correction'   → dossiers revenus en navette/correction
        //
        // Ces deux groupes forment le badge "action requise de la secrétaire".
        // Les autres statuts (en circulation chez les autres acteurs) ne font
        // PAS partie du badge — la secrétaire ne peut rien faire dessus.

        if ($role === 'secretaire') {
            return (int) DB::table('document_requests')
                ->whereIn('status', ['submitted', 'secretary_correction'])
                ->count();
        }

        // ── Tous les autres rôles : uniquement leur statut propre ─────────────
        //
        // Chaque acteur ne peut agir que sur son statut de file d'attente.
        // VISIBLE_STATUSES pour ces rôles contient un seul statut.

        $statuses = WorkflowConstants::VISIBLE_STATUSES[$role] ?? [];

        if (empty($statuses)) {
            return 0;
        }

        // Pour les rôles non-secrétaire, on filtre sur les statuts actionnables
        // (on exclut 'ready', 'delivered', 'rejected' qui ne nécessitent pas d'action)
        $actionableStatuses = array_filter($statuses, fn($s) => !in_array($s, [
            'ready_for_pickup', 'picked_up', 'rejected',
        ]));

        if (empty($actionableStatuses)) {
            return 0;
        }

        $query = DB::table('document_requests')
            ->whereIn('status', array_values($actionableStatuses));

        // Responsable Division : le badge ne doit compter que les dossiers
        // de son périmètre (formation_continue OU formation_distance).
        // La colonne sur l'utilisateur s'appelle chef_division_type (nom historique en BD).
        // Le rôle peut arriver sous le slug 'chef-division' ou 'responsable-division' —
        // canonicalRole() a déjà normalisé vers 'responsable-division' avant cet appel.
        if ($role === 'responsable-division' && !empty($user->chef_division_type)) {
            $query->where('responsable_division_type', $user->chef_division_type);
        }

        return (int) $query->count();
    }
}
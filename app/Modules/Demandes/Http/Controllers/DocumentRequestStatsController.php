<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DocumentRequestStatsController
 *
 * Responsabilité : statistiques agrégées pour les acteurs de la direction.
 *
 * Rôles autorisés : sec-da, directrice-adjointe, sec-dir, directeur, admin
 *
 * GET /api/document-requests/stats/direction
 */
class DocumentRequestStatsController extends Controller
{
    use ApiResponse;

    /**
     * Rôles qui ont accès aux stats de direction.
     * Le frontend décide quels indicateurs afficher pour chacun.
     */
    private const ALLOWED_ROLES = [
        'sec-da',
        'directrice-adjointe',
        'sec-dir',
        'directeur',
        'admin',
    ];

    public function direction(): JsonResponse
    {
        $user = Auth::user();
        $role = $user->roles->first()?->slug ?? null;

        if (!in_array($role, self::ALLOWED_ROLES)) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        // ── Statuts "en cours" (ni delivered ni rejected) ─────────────────────
        $enCoursStatuses = [
            'pending',
            'secretaire_correction',
            'comptable_review',
            'chef_division_review',
            'chef_cap_review',
            'sec_dir_adjointe_review',
            'directrice_adjointe_review',
            'sec_directeur_review',
            'directeur_review',
            'ready',
        ];

        // Toutes les agrégations en une seule requête via CASE WHEN
        $counts = DB::table('document_requests')
            ->selectRaw("
                COUNT(*) FILTER (WHERE status IN ('" . implode("','", $enCoursStatuses) . "'))
                    AS total_en_cours,
                COUNT(*) FILTER (WHERE status = 'pending')
                    AS total_pending,
                COUNT(*) FILTER (WHERE status = 'ready')
                    AS total_ready,
                COUNT(*) FILTER (
                    WHERE status = 'delivered'
                    AND delivered_at >= date_trunc('month', NOW())
                ) AS total_delivered_this_month,
                COUNT(*) FILTER (WHERE has_flag = true)
                    AS total_flagged,
                COUNT(*) FILTER (WHERE status = 'rejected')
                    AS total_rejected
            ")
            ->first();

        // ── Délai moyen de traitement (soumis → livré) ────────────────────────
        // Calculé sur les dossiers livrés au cours des 3 derniers mois
        // pour que la métrique soit représentative et rapide à calculer.
        $avgRow = DB::table('document_requests')
            ->whereNotNull('submitted_at')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '>=', now()->subMonths(3))
            ->selectRaw("
                ROUND(
                    AVG(
                        EXTRACT(EPOCH FROM (delivered_at - submitted_at)) / 86400.0
                    )::numeric,
                    1
                ) AS avg_days
            ")
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_en_cours'           => (int)  ($counts->total_en_cours           ?? 0),
                'total_pending'            => (int)  ($counts->total_pending            ?? 0),
                'total_ready'              => (int)  ($counts->total_ready              ?? 0),
                'total_delivered_this_month' => (int)($counts->total_delivered_this_month ?? 0),
                'total_flagged'            => (int)  ($counts->total_flagged            ?? 0),
                'total_rejected'           => (int)  ($counts->total_rejected           ?? 0),
                'avg_processing_days'      => (float)($avgRow->avg_days                ?? 0.0),
            ],
        ]);
    }
}

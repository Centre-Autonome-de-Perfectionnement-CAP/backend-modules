<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DocumentRequestHistoryController
 *
 * Responsabilité : exposer l'historique d'un dossier au frontend.
 *
 * Visibilité :
 *   • Tout acteur ayant accès au dossier peut voir l'historique complet.
 *   • Le champ `is_own_action` indique si l'entrée appartient au rôle
 *     courant — hint UI pour mettre en avant les propres actions de l'acteur
 *     (ex: filtre "Mes actions" activé par défaut dans le modal).
 *   • Le flag `has_flags` dans la réponse indique si au moins une entrée
 *     est de type `validation_flagged` — permet au frontend d'afficher
 *     un badge d'alerte sur le dossier sans lire toute la liste.
 */
class DocumentRequestHistoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DocumentRequestHistoryService $historyService
    ) {}

    // GET /api/attestations/document-requests/{id}/history

    public function index(int $id): JsonResponse
    {
        if (!DB::table('document_requests')->where('id', $id)->exists()) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        $role    = Auth::user()?->roles?->first()?->slug;
        $history = $this->historyService->getHistory($id, $role);

        // Pré-calcul utile pour le frontend : y a-t-il au moins une réserve ?
        $hasFlags = $history->contains('action_type', 'validation_flagged');

        return response()->json([
            'success'      => true,
            'data'         => $history,
            'current_role' => $role,
            'has_flags'    => $hasFlags,
            /*
             * Frontend :
             *   - Afficher $data en timeline chronologique dans le modal
             *   - Si $has_flags === true → afficher un badge ⚠ "Réserve(s) formulée(s)"
             *   - Par défaut : surligner les entrées is_own_action === true
             *   - Bouton "Tout voir" pour voir toutes les actions sans filtre
             */
        ]);
    }
}

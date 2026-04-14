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
 * Responsabilité : exposer l'historique complet d'un dossier.
 *
 * Chaque entrée retournée contient :
 *   - action_label    (string) — libellé lisible de l'action, incluant "Réserve acquittée"
 *   - is_own_action   (bool)   — true si l'entrée appartient au rôle de l'acteur courant
 */
class DocumentRequestHistoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DocumentRequestHistoryService $historyService
    ) {}

    public function index(int $id): JsonResponse
    {
        $exists = DB::table('document_requests')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        $user = Auth::user();
        $role = $user->roles->first()?->slug ?? null;

        $history = $this->historyService->getHistory($id, $role);

        return response()->json([
            'success'      => true,
            'data'         => $history,
            'current_role' => $role,
        ]);
    }
}

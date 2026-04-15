<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use Illuminate\Http\JsonResponse;

/**
 * Lecture de l'historique d'une demande.
 */
class DocumentRequestHistoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DocumentRequestHistoryService $historyService,
    ) {}

    public function index(int $id): JsonResponse
    {
        $entries = $this->historyService->getForDemande($id);

        return response()->json([
            'success' => true,
            'data'    => $entries,
        ]);
    }
}

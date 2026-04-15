<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lecture : liste et détail d'une demande.
 */
class DocumentRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DocumentRequestQueryService $queryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user    = Auth::user();
        $role    = $user->roles->first()?->slug ?? null;
        $filters = $request->only(['status', 'type', 'search']);

        $demandes = $this->queryService->listing($role, $user, $filters);

        return response()->json([
            'success' => true,
            'data'    => $demandes,
            'role'    => $role,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $demande = $this->queryService->findOrFail($id);
            return $this->successResponse($demande);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }
}

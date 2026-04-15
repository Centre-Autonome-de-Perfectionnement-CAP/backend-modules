<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\Http\Requests\TransitionRequest;
use App\Modules\Demandes\Services\TransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Applique une transition de workflow sur une demande.
 */
class DocumentRequestTransitionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TransitionService $transitionService,
    ) {}

    public function __invoke(TransitionRequest $request, int $id): JsonResponse
    {
        $role    = Auth::user()->roles->first()?->slug ?? null;
        $action  = $request->validated()['action'];
        $payload = $request->validated();

        try {
            $updated = $this->transitionService->apply($id, $action, $payload, $role);
            return $this->successResponse($updated, 'Statut mis à jour avec succès.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}

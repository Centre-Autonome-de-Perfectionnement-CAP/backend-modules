<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Statistiques pour les 4 rôles direction.
 */
class DocumentRequestStatsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DocumentRequestQueryService $queryService,
    ) {}

    public function __invoke(): JsonResponse
    {
        $user = Auth::user();
        $role = $user->roles->first()?->slug ?? null;

        if (!in_array($role, WorkflowConstants::DIRECTION_ROLES) && $role !== 'admin') {
            return $this->unauthorizedResponse('Statistiques réservées aux rôles direction.');
        }

        $stats = $this->queryService->statsForDirectionUser($user->id, $role);

        return $this->successResponse([
            'total_in_progress'   => $stats['totalInProgress'],
            'pending_at_my_level' => $stats['pendingAtMyLevel'],
            'total_validated'     => $stats['totalValidated'],
            'total_rejected'      => $stats['totalRejected'],
        ]);
    }
}

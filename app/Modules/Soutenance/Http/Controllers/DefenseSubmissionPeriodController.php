<?php

namespace App\Modules\Soutenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Soutenance\Http\Requests\CreateDefenseSubmissionPeriodRequest;
use App\Modules\Soutenance\Http\Requests\UpdateDefenseSubmissionPeriodRequest;
use App\Modules\Soutenance\Services\DefenseSubmissionPeriodService;
use App\Modules\Soutenance\Models\DefenseSubmissionPeriod;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefenseSubmissionPeriodController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DefenseSubmissionPeriodService $periodService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['academic_year_id']);
        $periods = $this->periodService->getAll($filters);

        return $this->successResponse($periods, 'Périodes récupérées avec succès');
    }

    public function store(CreateDefenseSubmissionPeriodRequest $request): JsonResponse
    {
        $period = $this->periodService->create($request->validated());

        return $this->createdResponse($period, 'Période créée avec succès');
    }

    public function update(UpdateDefenseSubmissionPeriodRequest $request, int $id): JsonResponse
    {
        $period = DefenseSubmissionPeriod::find($id);

        if (!$period) {
            return $this->errorResponse('Période non trouvée', 404);
        }

        $period = $this->periodService->update($period, $request->validated());

        return $this->successResponse($period, 'Période mise à jour avec succès');
    }

    public function destroy(int $id): JsonResponse
    {
        $period = DefenseSubmissionPeriod::find($id);

        if (!$period) {
            return $this->errorResponse('Période non trouvée', 404);
        }

        $this->periodService->delete($period);

        return $this->successResponse(null, 'Période supprimée avec succès');
    }

    public function getActivePeriod(): JsonResponse
    {
        $period = $this->periodService->getActivePeriod();

        if (!$period) {
            return $this->successResponse(null, 'Aucune période active');
        }

        return $this->successResponse($period, 'Période active récupérée avec succès');
    }
}

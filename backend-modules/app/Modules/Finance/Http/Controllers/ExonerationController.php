<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\ExonerationService;
use App\Modules\Finance\Models\Exoneration;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExonerationController extends Controller
{
    use ApiResponse;

    private ExonerationService $exonerationService;

    public function __construct(ExonerationService $exonerationService)
    {
        $this->exonerationService = $exonerationService;
    }

    public function index(Request $request)
    {
        $exonerations = $this->exonerationService->getAll($request->all());
        return $this->successResponse($exonerations, 'Exonérations récupérées');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_pending_student_id' => 'required|integer',
            'academic_year_id' => 'required|integer',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'reason' => 'nullable|string'
        ]);

        $exoneration = $this->exonerationService->create($validated);
        return $this->createdResponse($exoneration, 'Exonération créée');
    }

    public function update(Request $request, int $id)
    {
        $exoneration = Exoneration::findOrFail($id);
        
        $validated = $request->validate([
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'reason' => 'nullable|string'
        ]);

        $updated = $this->exonerationService->update($exoneration, $validated);
        return $this->updatedResponse($updated, 'Exonération mise à jour');
    }

    public function destroy(int $id)
    {
        $exoneration = Exoneration::findOrFail($id);
        $this->exonerationService->delete($exoneration);
        return $this->successResponse(null, 'Exonération supprimée');
    }
}

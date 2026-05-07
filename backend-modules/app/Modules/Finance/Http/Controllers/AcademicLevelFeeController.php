<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\CreateAcademicLevelFeeRequest;
use App\Modules\Finance\Http\Requests\UpdateAcademicLevelFeeRequest;
use App\Modules\Finance\Services\AcademicLevelFeeService;
use Illuminate\Http\Request;

class AcademicLevelFeeController extends Controller
{
    public function __construct(
        private AcademicLevelFeeService $service
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['academic_year_id', 'department_id', 'study_level', 'is_active']);
        $fees = $this->service->getAllFees($filters);

        return response()->json([
            'success' => true,
            'data' => $fees,
        ]);
    }

    public function store(CreateAcademicLevelFeeRequest $request)
    {
        $fee = $this->service->createFee($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tarif créé avec succès',
            'data' => $fee->load(['academicYear', 'department.cycle']),
        ], 201);
    }

    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'study_level' => 'nullable|string',
            'registration_fee' => 'required|numeric|min:0',
            'uemoa_training_fee' => 'required|numeric|min:0',
            'non_uemoa_training_fee' => 'required|numeric|min:0',
            'exempted_training_fee' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $fees = $this->service->createBulkFees($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tarifs créés avec succès',
            'data' => $fees,
        ], 201);
    }

    public function update(UpdateAcademicLevelFeeRequest $request, string $uuid)
    {
        $fee = $this->service->updateFee($uuid, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tarif mis à jour avec succès',
            'data' => $fee,
        ]);
    }

    public function destroy(string $uuid)
    {
        $this->service->deleteFee($uuid);

        return response()->json([
            'success' => true,
            'message' => 'Tarif supprimé avec succès',
        ]);
    }

    public function getStudentFee(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_id' => 'required|exists:departments,id',
            'study_level' => 'required|string',
            'origin' => 'required|in:uemoa,non_uemoa,exempted',
        ]);

        $fee = $this->service->getFeeForStudent(
            $request->academic_year_id,
            $request->department_id,
            $request->study_level,
            $request->origin
        );

        if (!$fee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun tarif trouvé pour ce niveau',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $fee,
        ]);
    }
}

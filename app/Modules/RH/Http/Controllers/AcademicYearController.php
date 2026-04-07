<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicYears = AcademicYear::all();

        return response()->json([
            'success' => true,
            'data' => $academicYears
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:academic_years',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If setting this as current year, deactivate others
        if ($request->is_current) {
            AcademicYear::query()->update(['is_current' => false]);
        }

        $academicYear = AcademicYear::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $academicYear
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $academicYear
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|unique:academic_years,name,'.$id,
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_current' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If setting this as current year, deactivate others
        if ($request->is_current) {
            AcademicYear::where('id', '!=', $id)->update(['is_current' => false]);
        }

        $academicYear->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $academicYear
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        // Prevent deletion if this is the current year
        if ($academicYear->is_current) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer l\'année académique courante'
            ], 400);
        }

        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Année académique supprimée avec succès'
        ]);
    }

    /**
     * Get the current academic year
     */
    public function getCurrent()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (!$currentYear) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année académique courante définie'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $currentYear
        ]);
    }

    /**
     * Set an academic year as current
     */
    public function setCurrent($id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        // Deactivate all other years
        AcademicYear::where('id', '!=', $id)->update(['is_current' => false]);

        // Activate this year
        $academicYear->update(['is_current' => true]);

        return response()->json([
            'success' => true,
            'data' => $academicYear
        ]);
    }
}

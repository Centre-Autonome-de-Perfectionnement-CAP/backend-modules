<?php

namespace App\Modules\Attendance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Attendance\Services\AttendanceExportService;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $service,
        protected AttendanceExportService $exportService,
    ) {
        // $this->middleware('auth:sanctum');
    }

    // =============================================
    // FILTERS — incluant les créneaux horaires
    // =============================================
    public function getFilters(): JsonResponse
    {
        $filieres = DB::table('filieres')->orderBy('name')->pluck('name');
        $annees   = DB::table('academic_years')->orderBy('year_start', 'desc')->pluck('academic_year');
        $salles   = DB::table('rooms')->where('is_available', true)->orderBy('name')->pluck('name');
        $matieres = DB::table('course_elements')->orderBy('name')->pluck('name')->unique()->values();

        // ✅ Créneaux horaires depuis le module EmploiDuTemps
        $timeSlots = DB::table('time_slots')
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time']);

        $heures = $timeSlots->map(function ($slot) {
            $start = substr($slot->start_time ?? '', 0, 5);
            $end   = substr($slot->end_time   ?? '', 0, 5);
            $label = $slot->name
                ? "{$slot->name} ({$start} - {$end})"
                : "{$start} - {$end}";
            return $label;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'filieres' => $filieres,
                'annees'   => $annees,
                'niveaux'  => ['L1', 'L2', 'L3'],
                'salles'   => $salles,
                'matieres' => $matieres,
                'heures'   => $heures, // ✅ créneaux horaires
            ],
        ]);
    }

    // =============================================
    // DASHBOARD
    // =============================================
    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getDashboardStats($request->all()),
        ]);
    }

    // =============================================
    // MANAGEMENT
    // =============================================
    public function management(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getManagementList($request->all()),
        ]);
    }

    // =============================================
    // EXPORT MANAGEMENT
    // =============================================
    public function export(Request $request)
    {
        $format  = $request->get('format', 'pdf');
        $filters = $request->only(['year', 'filiere', 'niveau', 'matiere', 'salle', 'heure']);

        return match ($format) {
            'excel' => $this->exportService->exportExcel($filters),
            'word'  => $this->exportService->exportWord($filters),
            default => $this->exportService->exportPdf($filters),
        };
    }

    // =============================================
    // FINGERPRINT LIST
    // =============================================
    public function fingerprint(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getFingerprintList($request->all()),
        ]);
    }

    // =============================================
    // EXPORT FINGERPRINT
    // =============================================
    public function exportFingerprint(Request $request)
    {
        $format  = $request->get('format', 'pdf');
        $filters = $request->only(['annee', 'filiere', 'niveau']);

        return match ($format) {
            'excel' => $this->exportService->exportFingerprintExcel($filters),
            'word'  => $this->exportService->exportFingerprintWord($filters),
            default => $this->exportService->exportFingerprintPdf($filters),
        };
    }

    // =============================================
    // STORE FINGERPRINT
    // =============================================
    public function storeFingerprint(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'   => 'required|string',
            'fingerprint' => 'required|boolean',
        ]);

        $result = $this->service->storeFingerprint($request->all());

        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte enregistrée']);
    }

    // =============================================
    // UPDATE FINGERPRINT
    // =============================================
    public function updateFingerprint(Request $request, int $id): JsonResponse
    {
        $request->validate(['fingerprint' => 'required|boolean']);

        $result = $this->service->updateFingerprint($id, $request->input('fingerprint'));

        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte mise à jour']);
    }

    // =============================================
    // DELETE FINGERPRINT
    // =============================================
    public function deleteFingerprint(int $id): JsonResponse
    {
        $result = $this->service->deleteFingerprint($id);

        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte réinitialisée']);
    }

    // =============================================
    // SCAN
    // =============================================
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'student_id'        => 'required|integer|exists:students,id',
            'course_element_id' => 'required|integer|exists:course_elements,id',
            'status'            => 'required|in:present,absent',
            'date'              => 'required|date',
        ]);

        $attendance = $this->service->recordAttendance($request->all());

        return response()->json(['success' => true, 'data' => $attendance]);
    }
}

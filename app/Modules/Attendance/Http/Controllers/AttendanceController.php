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
        protected AttendanceService       $service,
        protected AttendanceExportService $exportService,
    ) {
        // $this->middleware('auth:sanctum');
    }

    // =========================================================
    // FILTERS
    // Retourne : filières, années, niveaux, matières, heures
    // Heures : depuis emploi_du_temps → "Lundi 08:00 - 12:00"
    // =========================================================
    public function getFilters(): JsonResponse
    {
        $filieres = DB::table('departments')
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name');

        $annees = DB::table('academic_years')
            ->orderByDesc('year_start')
            ->pluck('academic_year');

        $niveaux = DB::table('class_groups')
            ->distinct()
            ->orderBy('study_level')
            ->pluck('study_level');

        $matieres = DB::table('course_elements')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $dayLabels = [
            'monday'    => 'Lundi',   'tuesday'  => 'Mardi',
            'wednesday' => 'Mercredi','thursday' => 'Jeudi',
            'friday'    => 'Vendredi','saturday' => 'Samedi',
            'sunday'    => 'Dimanche',
        ];

        $emplois = DB::table('emploi_du_temps')
            ->where('is_cancelled', 0)
            ->where('is_active', 1)
            ->select('day_of_week', 'start_time', 'end_time')
            ->distinct()
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->orderBy('start_time')
            ->get();

        $heures = $emplois->map(function ($row) use ($dayLabels) {
            $day   = $dayLabels[$row->day_of_week] ?? ucfirst($row->day_of_week);
            $start = substr($row->start_time ?? '', 0, 5);
            $end   = substr($row->end_time   ?? '', 0, 5);
            return "{$day} {$start} - {$end}";
        })->unique()->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'filieres' => $filieres,
                'annees'   => $annees,
                'niveaux'  => $niveaux->isEmpty() ? collect(['L1', 'L2', 'L3']) : $niveaux,
                'matieres' => $matieres,
                'heures'   => $heures,
            ],
        ]);
    }

    // =========================================================
    // DASHBOARD — stats globales + taux par mois (Jan→Déc)
    // Paramètres : annee, filiere, niveau
    // =========================================================
    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getDashboardStats($request->all()),
        ]);
    }

    // =========================================================
    // MANAGEMENT — liste présences filtrées
    // Paramètres : year, filiere, niveau, matiere, heure
    // =========================================================
    public function management(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getManagementList($request->all()),
        ]);
    }

    // =========================================================
    // EXPORT MANAGEMENT — ?format=pdf|excel|word
    // =========================================================
    public function export(Request $request)
    {
        $format  = $request->get('format', 'pdf');
        $filters = $request->only(['year', 'filiere', 'niveau', 'matiere', 'heure']);

        return match ($format) {
            'excel' => $this->exportService->exportExcel($filters),
            'word'  => $this->exportService->exportWord($filters),
            default => $this->exportService->exportPdf($filters),
        };
    }

    // =========================================================
    // FINGERPRINT — liste étudiants + statut empreinte
    // Paramètres : annee, filiere, niveau
    // =========================================================
    public function fingerprint(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getFingerprintList($request->all()),
        ]);
    }

    // =========================================================
    // EXPORT FINGERPRINT — ?format=pdf|excel|word
    // ⚠️ Déclarée AVANT /fingerprint/{id} dans api.php
    // =========================================================
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

    // =========================================================
    // STORE FINGERPRINT
    // =========================================================
    public function storeFingerprint(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'   => 'required|string',
            'fingerprint' => 'required|boolean',
        ]);

        if (!$this->service->storeFingerprint($request->all())) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte enregistrée']);
    }

    // =========================================================
    // UPDATE FINGERPRINT
    // =========================================================
    public function updateFingerprint(Request $request, int $id): JsonResponse
    {
        $request->validate(['fingerprint' => 'required|boolean']);

        if (!$this->service->updateFingerprint($id, $request->input('fingerprint'))) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte mise à jour']);
    }

    // =========================================================
    // DELETE FINGERPRINT
    // =========================================================
    public function deleteFingerprint(int $id): JsonResponse
    {
        if (!$this->service->deleteFingerprint($id)) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte réinitialisée']);
    }

    // =========================================================
    // SCAN — enregistrer une présence
    // =========================================================
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'student_id'        => 'required|integer|exists:students,id',
            'course_element_id' => 'required|integer|exists:course_elements,id',
            'status'            => 'required|in:present,absent',
            'date'              => 'required|date',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->service->recordAttendance($request->all()),
        ]);
    }
}
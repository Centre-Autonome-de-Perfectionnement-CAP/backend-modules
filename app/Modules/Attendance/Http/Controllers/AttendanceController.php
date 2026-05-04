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
    ) {}

    // ── Filtres dynamiques ────────────────────────────────────────────────────
    public function getFilters(): JsonResponse
    {
        $filieres = DB::table('departments')
            ->where('is_active', 1)->orderBy('name')->pluck('name');

        $annees = DB::table('academic_years')
            ->orderByDesc('year_start')->pluck('academic_year');

        $niveaux = DB::table('class_groups')
            ->distinct()->orderBy('study_level')->pluck('study_level');

        $matieres = DB::table('course_elements')
            ->orderBy('name')->pluck('name')->unique()->values();

        $courseElements = DB::table('course_elements')
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name]);

        $dayLabels = [
            'monday' => 'Lundi',    'tuesday'  => 'Mardi',
            'wednesday' => 'Mercredi', 'thursday' => 'Jeudi',
            'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
        ];

        $emplois = DB::table('emploi_du_temps')
            ->where('is_cancelled', 0)->where('is_active', 1)
            ->select('day_of_week', 'start_time', 'end_time')->distinct()
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->orderBy('start_time')->get();

        $heures = $emplois->map(function ($row) use ($dayLabels) {
            $day   = $dayLabels[$row->day_of_week] ?? ucfirst($row->day_of_week);
            $start = substr($row->start_time ?? '', 0, 5);
            $end   = substr($row->end_time   ?? '', 0, 5);
            return "{$day} {$start} - {$end}";
        })->unique()->values();

        return response()->json([
            'success' => true,
            'data'    => compact('filieres', 'annees', 'niveaux', 'matieres', 'heures', 'courseElements'),
        ]);
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────
    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getDashboardStats($request->all()),
        ]);
    }

    // ── Management ────────────────────────────────────────────────────────────
    public function management(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getManagementList($request->all()),
        ]);
    }

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

    // ── Séances disponibles pour un cours donné ───────────────────────────────
    public function sessions(Request $request): JsonResponse
    {
        $request->validate(['course_element_id' => 'required|integer']);

        $sessions = DB::table('attendances')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->where('attendances.course_element_id', $request->course_element_id)
            ->selectRaw('
                DATE(attendances.date) as date,
                course_elements.name   as matiere,
                COUNT(*)               as total,
                SUM(attendances.status = "present") as presents,
                SUM(attendances.status = "absent")  as absents,
                SUM(attendances.status = "present" AND attendances.on_time = 0) as retards
            ')
            ->groupByRaw('DATE(attendances.date), course_elements.name')
            ->orderByDesc('date')
            ->get()
            ->map(fn($r) => [
                'date'     => $r->date,
                'matiere'  => $r->matiere,
                'total'    => (int) $r->total,
                'presents' => (int) $r->presents,
                'absents'  => (int) $r->absents,
                'retards'  => (int) $r->retards,
                'label'    => \Carbon\Carbon::parse($r->date)->translatedFormat('l d F Y')
                              . " — {$r->presents}/{$r->total} présents",
            ]);

        return response()->json(['success' => true, 'data' => $sessions]);
    }

    // ── Liste par cours / séance ───────────────────────────────────────────────
    public function courseAttendance(Request $request): JsonResponse
    {
        $request->validate(['course_element_id' => 'required|integer']);
        return response()->json([
            'success' => true,
            'data'    => $this->service->getCourseAttendanceList($request->all()),
        ]);
    }

    public function exportCourseAttendance(Request $request)
    {
        $format  = $request->get('format', 'pdf');
        $filters = $request->only(['course_element_id', 'date', 'filiere', 'niveau']);
        return match ($format) {
            'excel' => $this->exportService->exportCourseExcel($filters),
            'word'  => $this->exportService->exportCourseWord($filters),
            default => $this->exportService->exportCoursePdf($filters),
        };
    }

    // ── Statut capteur ────────────────────────────────────────────────────────
    public function sensorStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getSensorStatus(),
        ]);
    }

    // ── Fingerprint CRUD ─────────────────────────────────────────────────────
    public function fingerprint(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getFingerprintList($request->all()),
        ]);
    }

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

    // updateFingerprint — accepte maintenant fingerprint_index en plus de fingerprint
    public function updateFingerprint(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fingerprint'       => 'required|boolean',
            'fingerprint_index' => 'nullable|integer|min:1|max:127',
        ]);

        if (!$this->service->updateFingerprint(
            $id,
            $request->input('fingerprint'),
            $request->input('fingerprint_index')   // null si non fourni
        )) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Empreinte mise à jour']);
    }

    public function deleteFingerprint(int $id): JsonResponse
    {
        if (!$this->service->deleteFingerprint($id)) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Empreinte réinitialisée']);
    }

    // ── SCAN — reçoit les données de l'Arduino ────────────────────────────────
    // Format Arduino : { fingerprint_index, date }
    // on_time est calculé côté Laravel — plus fiable que l'Arduino
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint_index' => 'required|integer|min:1|max:127',
            'date'              => 'required|date',
            'status'            => 'nullable|in:present,absent',
        ]);

        $result = $this->service->recordAttendanceFromArduino($request->all());

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}
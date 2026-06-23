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
            'monday' => 'Lundi', 'tuesday' => 'Mardi',
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

    // ── Séances disponibles ───────────────────────────────────────────────────
    public function sessions(Request $request): JsonResponse
    {
        $request->validate(['course_element_id' => 'required|integer']);
        return response()->json([
            'success' => true,
            'data'    => $this->service->sessions($request->all()),
        ]);
    }

    // ── Liste par cours / séance ──────────────────────────────────────────────
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

    // ── Fingerprint ───────────────────────────────────────────────────────────
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
        $request->validate(['matricule' => 'required|string', 'fingerprint' => 'required|boolean']);
        if (!$this->service->storeFingerprint($request->all())) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Empreinte enregistrée']);
    }

    public function updateFingerprint(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fingerprint'       => 'required|boolean',
            'fingerprint_index' => 'nullable|integer|min:1|max:127',
        ]);
        if (!$this->service->updateFingerprint($id, $request->boolean('fingerprint'), $request->input('fingerprint_index'))) {
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

    // ── Effacer TOUTES les empreintes (appelé après clear-all ESP32) ──────────
    public function clearAllFingerprints(): JsonResponse
    {
        $count = DB::table('students')
            ->where('fingerprint_status', true)
            ->count();

        DB::table('students')->update([
            'fingerprint_status' => false,
            'fingerprint_index'  => null,
            'updated_at'         => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} empreinte(s) réinitialisée(s) en base de données",
            'count'   => $count,
        ]);
    }

    // ── SCAN depuis Arduino — entrée / sortie ─────────────────────────────────
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint_index' => 'required|integer|min:1|max:127',
            'date'              => 'required|date',
            'time'              => 'required|date_format:H:i:s',
        ]);

        $result = $this->service->recordAttendanceFromArduino($request->all());

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result, 200);
    }

    // ── Auto-clôture des cours terminés (appelée en polling par le frontend) ──
    public function autoClose(): JsonResponse
    {
        $result = $this->service->autoCloseFinishedCourses();
        return response()->json(['success' => true, 'data' => $result]);
    }

    // ── Profil étudiant — historique complet ─────────────────────────────────
    public function studentProfile(int $id): JsonResponse
    {
        $result = $this->service->getStudentProfile($id);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    // ── CORRIGÉ : Clôturer un cours manuellement (Absences ciblées - Option 1) ──
    public function closeCourse(Request $request): JsonResponse
    {
        // On récupère les filtres envoyés par le bouton Option 1 du tableau de gestion
        $filters = $request->only(['annee', 'filiere', 'niveau', 'matiere', 'heure']);
        $date    = $request->input('date', now()->format('Y-m-d'));

        // On passe les filtres structurels directement au service pour générer les absences ciblées
        $result = $this->service->closeCourseSessionWithFilters($filters, $date);
        
        $status = $result['success'] ? 200 : 400;
        return response()->json($result, $status);
    }

    // ── Historique des scans d'un étudiant pour une date ─────────────────────
    public function scanHistory(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer',
            'date'       => 'required|date',
        ]);

        $scans = DB::table('attendance_scans')
            ->where('student_id', $request->student_id)
            ->where('date', $request->date)
            ->orderBy('scan_time')
            ->get(['scan_time', 'scan_type']);

        return response()->json(['success' => true, 'data' => $scans]);
    }
}
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
    // Format attendu : { fingerprint_index, date, time }
    // "time" = heure exacte du scan ex: "18:07:23"
    // Laravel détermine automatiquement entry ou exit
    // et calcule le statut (présent / retard / absent)
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

    // ── Historique des scans d'un étudiant pour une date ─────────────────────
    // Utile pour debug ou affichage détaillé
    // ── Profil étudiant — historique complet ─────────────────────────────────
    // ── Server-Sent Events — pousse les nouveaux scans en temps réel ──────
    public function liveStream(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $lastScanId = DB::table('attendance_scans')->max('id') ?? 0;

        // Ping initial pour confirmer la connexion
        echo "event: ping
data: connected

";
        ob_flush(); flush();

        $maxTime = time() + 55; // max 55s (avant timeout nginx/apache)

        while (time() < $maxTime) {
            // Chercher les nouveaux scans depuis le dernier ID connu
            $newScans = DB::table('attendance_scans')
                ->join('students',        'attendance_scans.student_id',        '=', 'students.id')
                ->join('course_elements', 'attendance_scans.course_element_id', '=', 'course_elements.id')
                ->where('attendance_scans.id', '>', $lastScanId)
                ->select(
                    'attendance_scans.id',
                    'attendance_scans.student_id',
                    'attendance_scans.scan_type',
                    'attendance_scans.scan_time',
                    'course_elements.name as matiere',
                    DB::raw("CONCAT(students.first_name,' ',students.last_name) as student"),
                )
                ->get();

            foreach ($newScans as $scan) {
                // Récupérer le late_type depuis attendances
                $att = DB::table('attendances')
                    ->where('student_id',        $scan->student_id)
                    ->where('course_element_id', DB::table('attendance_scans')->where('id',$scan->id)->value('course_element_id'))
                    ->whereDate('date', now()->format('Y-m-d'))
                    ->value('late_type');

                $data = json_encode([
                    'student_id' => $scan->student_id,
                    'student'    => $scan->student,
                    'scan_type'  => $scan->scan_type,
                    'scan_time'  => $scan->scan_time,
                    'matiere'    => $scan->matiere,
                    'late_type'  => $att,
                ]);

                echo "event: scan
data: {$data}

";
                ob_flush(); flush();
                $lastScanId = max($lastScanId, $scan->id);
            }

            // Ping toutes les 5s pour maintenir la connexion
            echo "event: ping
data: ok

";
            ob_flush(); flush();

            if (connection_aborted()) break;
            sleep(3);
        }
    }

    // ── Données du cours actif (pour LiveCourse.tsx) ───────────────────────
    public function liveCourse(): JsonResponse
    {
        $sensor = $this->service->getSensorStatus();
        if (!isset($sensor['active']) || !$sensor['active']) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $courseElementId = $sensor['course_element_id'];
        $date = now()->format('Y-m-d');

        $students = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->where('attendances.course_element_id', $courseElementId)
            ->whereDate('attendances.date', $date)
            ->select(
                'students.id',
                'students.matricule',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
                'attendances.status',
                'attendances.late_type',
                'attendances.first_entry',
                'attendances.last_exit',
                'attendances.total_minutes',
            )
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();

        return response()->json(['success' => true, 'data' => $students]);
    }

    public function studentProfile(int $id): JsonResponse
    {
        $result = $this->service->getStudentProfile($id);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    // ── Clôturer un cours manuellement (marquer absents les non-pointeurs) ──
    public function closeCourse(Request $request): JsonResponse
    {
        $courseElementId = $request->input('course_element_id');
        $date            = $request->input('date', now()->format('Y-m-d'));

        if (!$courseElementId) {
            return response()->json(['success' => false, 'message' => 'course_element_id requis'], 422);
        }

        $result = $this->service->closeCourseSession((int)$courseElementId, $date);
        $status = $result['success'] ? 200 : 400;
        return response()->json($result, $status);
    }

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

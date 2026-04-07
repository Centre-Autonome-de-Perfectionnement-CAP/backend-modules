<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\EmploiDuTemps;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\ClassGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PdfController
 *
 * Génère un PDF d'emploi du temps pour une semaine (lundi→dimanche).
 *
 * Route : POST /api/emploi-temps/pdf/download
 * Body  : {
 *   "week_start"       : "2026-03-23",   // n'importe quelle date dans la semaine
 *   "class_group_id"   : 5,              // optionnel
 *   "academic_year_id" : 1               // optionnel
 * }
 *
 * ─── Emplacement du script Python ───────────────────────────────────────────
 * Placer  generate_edt_pdf.py  dans :
 *   backend-modules/scripts/generate_edt_pdf.py
 *
 * ─── Installation de reportlab (une seule fois) ──────────────────────────────
 * Windows : pip install reportlab
 * Linux   : pip install reportlab  OU  pip3 install reportlab
 */
class PdfController extends Controller
{
    // ─── Chemin du script Python ──────────────────────────────────────────────
    // base_path() pointe vers la racine du projet Laravel
    // (là où se trouve le dossier vendor/, app/, etc.)
    private function getScriptPath(): string
    {
        return base_path('scripts' . DIRECTORY_SEPARATOR . 'generate_edt_pdf.py');
    }

    // ─── Commande Python selon l'OS ───────────────────────────────────────────
    private function getPythonCmd(): string
    {
        // Sur Windows, 'python' est souvent dans le PATH ; sur Linux, 'python3'
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'python';
        }
        return 'python3';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/emploi-temps/pdf/download
    // ──────────────────────────────────────────────────────────────────────────

    public function download(Request $request): JsonResponse|BinaryFileResponse
    {
        // ── Validation ────────────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'week_start'       => 'required|date_format:Y-m-d',
            'class_group_id'   => 'nullable|integer|exists:class_groups,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ── Semaine (lundi → dimanche) ────────────────────────────────────────
        $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // ── Récupération des créneaux de la semaine ───────────────────────────
        $query = EmploiDuTemps::with([
            'room.building',
            'classGroup',
            'academicYear',
            'department',
            'program.courseElementProfessor.courseElement',
            'program.courseElementProfessor.professor',
        ])
        ->where('is_active', true)
        ->where('is_cancelled', false);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('class_group_id')) {
            $query->where('class_group_id', $request->class_group_id);
        }

        $records = $query->get();

        // ── Construction du planning par jour ─────────────────────────────────
        $schedule = [];

        foreach ($records as $rec) {
            $day = $rec->day_of_week; // 'monday', 'tuesday', …

            // Pour un cours récurrent : valide si pas encore expiré
            if ($rec->is_recurring && $rec->recurrence_end_date) {
                if (Carbon::parse($rec->recurrence_end_date)->lt($weekStart)) {
                    continue; // créneau expiré avant cette semaine
                }
            }

            $cep        = $rec->program?->courseElementProfessor;
            $courseName = $this->cleanUtf8($cep?->courseElement?->name ?? 'Cours');
            $professors = [];

            if ($cep?->professor) {
                $p = $cep->professor;
                $professors[] = $this->cleanUtf8(trim("Dr {$p->last_name} {$p->first_name}"));
                if (!empty($p->phone)) {
                    $professors[] = $this->cleanUtf8($p->phone);
                }
            }

            // Format horaire : "18h-22h"
            $timeSlot = '';
            if ($rec->start_time && $rec->end_time) {
                $sh = Carbon::createFromFormat('H:i:s', strlen($rec->start_time) === 5
                    ? $rec->start_time . ':00' : $rec->start_time)->format('H');
                $eh = Carbon::createFromFormat('H:i:s', strlen($rec->end_time) === 5
                    ? $rec->end_time . ':00' : $rec->end_time)->format('H');
                $timeSlot = "{$sh}h-{$eh}h";
            }

            $roomName = '';
            if ($rec->room) {
                $roomName = $this->cleanUtf8('Salle ' . $rec->room->name);
            }

            $schedule[$day][] = [
                'course_name' => $courseName,
                'room'        => $roomName,
                'professors'  => $professors,
                'time_slot'   => $timeSlot,
            ];
        }

        // ── Métadonnées ───────────────────────────────────────────────────────
        $classGroup   = $request->filled('class_group_id')
            ? ClassGroup::find($request->class_group_id) : null;
        $academicYear = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id) : null;

        $className = $classGroup
            ? $this->cleanUtf8("de {$classGroup->group_name} ({$classGroup->study_level})")
            : 'des étudiants';

        $periodStr = $weekStart->format('d/m/y') . ' au ' . $weekEnd->format('d/m/y');

        // Construire la note NB avec les noms de cours distincts
        $courseNames = collect($records)
            ->map(fn($r) => $r->program?->courseElementProfessor?->courseElement?->name)
            ->filter()
            ->unique()
            ->map(fn($n) => $this->cleanUtf8($n))
            ->values()
            ->toArray();
        $nbNote = implode(' / ', $courseNames);

        // ── JSON des données pour le script Python ────────────────────────────
        $pdfData = [
            'school_name'       => "UNIVERSITE D'ABOMEY-CALAVI",
            'school_name2'      => "ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI",
            'school_name3'      => 'CENTRE AUTONOME DE PERFECTIONNEMENT',
            'ref_code'          => 'UAC/EPAC/CAP-RdivFC',
            'class_name'        => $className,
            'period'            => $periodStr,
            'nb_note'           => $nbNote,
            'signature_left'    => 'Le Responsable Division Formation Continue',
            'name_left'         => '',
            'signature_right'   => 'Le Chef CAP',
            'name_right'        => '',
            'schedule'          => $schedule,
        ];

        // ── Fichier de sortie temporaire ──────────────────────────────────────
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . 'edt_' . time() . '_' . uniqid() . '.pdf';

        // ── Sérialisation JSON (UTF-8 garanti) ────────────────────────────────
        $jsonString = json_encode($pdfData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonString === false) {
            return response()->json([
                'message' => 'Erreur de sérialisation JSON : ' . json_last_error_msg(),
            ], 500);
        }

        // ── Écriture du JSON dans un fichier temporaire ───────────────────────
        // (évite les problèmes d'échappement shell avec les caractères spéciaux)
        $jsonTmpFile = $tempDir . DIRECTORY_SEPARATOR . 'edt_data_' . time() . '.json';
        file_put_contents($jsonTmpFile, $jsonString, LOCK_EX);

        // ── Exécution du script Python ────────────────────────────────────────
        $python     = $this->getPythonCmd();
        $scriptPath = $this->getScriptPath();

        // Vérification que le script existe
        if (!file_exists($scriptPath)) {
            @unlink($jsonTmpFile);
            return response()->json([
                'message' => "Script Python introuvable : {$scriptPath}",
                'hint'    => 'Placez generate_edt_pdf.py dans le dossier scripts/ à la racine du projet.',
            ], 500);
        }

        // Construction de la commande (passer le fichier JSON plutôt que le JSON brut)
        $cmd = sprintf(
            '%s %s --json-file %s --output %s 2>&1',
            escapeshellcmd($python),
            escapeshellarg($scriptPath),
            escapeshellarg($jsonTmpFile),
            escapeshellarg($outputPath)
        );

        $rawOutput = shell_exec($cmd);

        // Nettoyage du fichier JSON temporaire
        @unlink($jsonTmpFile);

        // Convertir la sortie en UTF-8 (Windows retourne du CP1252)
        $cleanOutput = $this->toUtf8($rawOutput ?? '');

        // ── Vérification du PDF généré ────────────────────────────────────────
        if (!file_exists($outputPath) || filesize($outputPath) < 200) {
            return response()->json([
                'message' => 'Échec de la génération du PDF.',
                'detail'  => $cleanOutput ?: 'Aucune sortie du script Python.',
                'hint'    => 'Vérifiez que Python et reportlab sont installés : pip install reportlab',
            ], 500);
        }

        // ── Téléchargement ────────────────────────────────────────────────────
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $classGroup?->group_name ?? 'edt');
        $filename = "emploi_du_temps_{$safeName}_{$weekStart->format('Y-m-d')}.pdf";

        return response()->download($outputPath, $filename, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

 
    private function toUtf8(string $str): string
    {
        if (mb_detect_encoding($str, 'UTF-8', true)) {
            return $str;
        }
        // Tentative de conversion CP1252 → UTF-8
        $converted = @mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
        return $converted !== false ? $converted : mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }

    /**
     * Assure qu'une chaîne est en UTF-8 valide (remplace les octets invalides).
     */
    private function cleanUtf8(string $str): string
    {
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        return mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str) ?: 'Windows-1252');
    }
}
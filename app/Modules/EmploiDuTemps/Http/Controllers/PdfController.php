<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\EmploiDuTemps;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\ClassGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * PdfController
 *
 * Génère un PDF d'emploi du temps pour une semaine (lundi→dimanche).
 * Utilise DomPDF — zéro dépendance externe (Python/reportlab supprimé).
 *
 * Route : POST /api/emploi-du-temps/pdf/download
 * Body  : {
 *   "week_start"       : "2026-03-23",
 *   "class_group_id"   : 5,
 *   "academic_year_id" : 1
 * }
 */
class PdfController extends Controller
{
    public function download(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
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

        try {
            // ── Semaine (lundi → dimanche) ────────────────────────────────────
            $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY);
            $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            // ── Récupération des créneaux ─────────────────────────────────────
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

            // ── Construction du planning par jour ─────────────────────────────
            $schedule = [];

            foreach ($records as $rec) {
                $day = $rec->day_of_week;

                if ($rec->is_recurring && $rec->recurrence_end_date) {
                    if (Carbon::parse($rec->recurrence_end_date)->lt($weekStart)) {
                        continue;
                    }
                }

                $cep        = $rec->program?->courseElementProfessor;
                $courseName = $cep?->courseElement?->name ?? 'Cours';
                $professors = [];

                if ($cep?->professor) {
                    $p = $cep->professor;
                    $professors[] = trim("Dr {$p->last_name} {$p->first_name}");
                    if (!empty($p->phone)) {
                        $professors[] = $p->phone;
                    }
                }

                $timeSlot = '';
                if ($rec->start_time && $rec->end_time) {
                    $fmt = fn($t) => strlen($t) === 5 ? $t . ':00' : $t;
                    $sh  = Carbon::createFromFormat('H:i:s', $fmt($rec->start_time))->format('H');
                    $eh  = Carbon::createFromFormat('H:i:s', $fmt($rec->end_time))->format('H');
                    $timeSlot = "{$sh}h-{$eh}h";
                }

                $roomName = $rec->room ? ('Salle ' . $rec->room->name) : '';

                $schedule[$day][] = [
                    'course_name' => $courseName,
                    'room'        => $roomName,
                    'professors'  => $professors,
                    'time_slot'   => $timeSlot,
                ];
            }

            // ── Métadonnées ───────────────────────────────────────────────────
            $classGroup   = $request->filled('class_group_id')
                ? ClassGroup::find($request->class_group_id) : null;

            $className = $classGroup
                ? "de {$classGroup->group_name} ({$classGroup->study_level})"
                : 'des étudiants';

            $periodStr = $weekStart->format('d/m/y') . ' au ' . $weekEnd->format('d/m/y');

            $courseNames = collect($records)
                ->map(fn($r) => $r->program?->courseElementProfessor?->courseElement?->name)
                ->filter()->unique()->values()->toArray();
            $nbNote = implode(' / ', $courseNames);

            // ── Données pour le template Blade ────────────────────────────────
            $data = [
                'school_name'     => "UNIVERSITE D'ABOMEY-CALAVI",
                'school_name2'    => "ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI",
                'school_name3'    => 'CENTRE AUTONOME DE PERFECTIONNEMENT',
                'ref_code'        => 'UAC/EPAC/CAP-RdivFC',
                'class_name'      => $className,
                'period'          => $periodStr,
                'nb_note'         => $nbNote,
                'signature_left'  => 'Le Responsable Division Formation Continue',
                'name_left'       => '',
                'signature_right' => 'Le Chef CAP',
                'name_right'      => '',
                'schedule'        => $schedule,
            ];

            // ── Génération DomPDF ─────────────────────────────────────────────
            $pdf = Pdf::loadView('pdf.emploi-du-temps', $data)
                ->setPaper('a4', 'landscape');

            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $classGroup?->group_name ?? 'edt');
            $filename = "emploi_du_temps_{$safeName}_{$weekStart->format('Y-m-d')}.pdf";

            return $pdf->download($filename);

        } catch (\Throwable $e) {
            \Log::error('[PdfController] Erreur génération PDF emploi du temps : ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Erreur lors de la génération du PDF.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Erreur interne.',
            ], 500);
        }
    }

    /**
     * Aperçu inline (même génération, Content-Disposition: inline)
     */
    public function preview(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        return $this->download($request);
    }
}

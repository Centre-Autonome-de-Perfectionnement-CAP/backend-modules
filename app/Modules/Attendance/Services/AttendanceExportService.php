<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use App\Modules\Core\Services\PdfService;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class AttendanceExportService
{
    public function __construct(protected PdfService $pdfService) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPERS PRIVÉS
    // ─────────────────────────────────────────────────────────────────────────

    private function parseHeure(string $heure): ?array
    {
        $dayMap = [
            'lundi'    => 'monday',  'mardi'    => 'tuesday',
            'mercredi' => 'wednesday','jeudi'   => 'thursday',
            'vendredi' => 'friday',  'samedi'   => 'saturday',
            'dimanche' => 'sunday',
        ];
        if (!preg_match('/^(\w+)\s+(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/iu', trim($heure), $m)) {
            return null;
        }
        $dayEn = $dayMap[mb_strtolower(trim($m[1]))] ?? null;
        if (!$dayEn) return null;
        return ['day_of_week' => $dayEn, 'start_time' => $m[2], 'end_time' => $m[3]];
    }

    private function normalizeFilters(array $filters): array
    {
        if (!empty($filters['year']) && empty($filters['annee'])) {
            $filters['annee'] = $filters['year'];
        }
        if (!empty($filters['annee']) && empty($filters['year'])) {
            $filters['year'] = $filters['annee'];
        }
        return $filters;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DONNÉES POUR LES EXPORTS PAR SÉANCE
    //  Retourne students (objets) + summary + meta (matiere, date, filiere...)
    // ─────────────────────────────────────────────────────────────────────────

    private function getCourseExportData(array $filters): array
    {
        $dayLabels = [
            'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
        ];

        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('departments', 'students.filiere_id', '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'attendances.room_id', '=', 'rooms.id')
            ->leftJoin('emploi_du_temps', function ($join) {
                $join->on('emploi_du_temps.room_id', '=', 'attendances.room_id')
                     ->whereRaw("emploi_du_temps.day_of_week = LOWER(DAYNAME(attendances.date))")
                     ->where('emploi_du_temps.is_cancelled', 0)
                     ->where('emploi_du_temps.is_active', 1);
            })
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'attendances.status',
                'attendances.on_time',
                'attendances.date',
                'course_elements.name as matiere',
                'students.niveau',
                'departments.name as filiere',
                'academic_years.academic_year as annee',
                'rooms.name as salle',
                'emploi_du_temps.day_of_week as edt_day',
                'emploi_du_temps.start_time as edt_start',
                'emploi_du_temps.end_time as edt_end',
            );

        if (!empty($filters['course_element_id'])) {
            $query->where('attendances.course_element_id', $filters['course_element_id']);
        }
        if (!empty($filters['date'])) {
            $query->whereDate('attendances.date', $filters['date']);
        }
        if (!empty($filters['filiere'])) {
            $query->where('departments.name', $filters['filiere']);
        }
        if (!empty($filters['niveau'])) {
            $query->where('students.niveau', $filters['niveau']);
        }

        $rows = $query->orderBy('students.last_name')->get()
            ->map(function ($row) use ($dayLabels) {
                $heure = null;
                if (!empty($row->edt_day)) {
                    $day   = $dayLabels[$row->edt_day] ?? ucfirst($row->edt_day);
                    $start = substr($row->edt_start ?? '', 0, 5);
                    $end   = substr($row->edt_end   ?? '', 0, 5);
                    $heure = "{$day} {$start} - {$end}";
                }
                return (object)[
                    'name'      => $row->name      ?? 'N/A',
                    'matricule' => $row->matricule  ?? 'N/A',
                    'phone'     => $row->phone      ?? null,
                    'status'    => $row->status     ?? 'absent',
                    'on_time'   => (bool)($row->on_time ?? true),
                    'date'      => $row->date       ?? '',
                    'matiere'   => $row->matiere    ?? 'N/A',
                    'niveau'    => $row->niveau     ?? 'N/A',
                    'filiere'   => $row->filiere    ?? 'N/A',
                    'annee'     => $row->annee      ?? 'N/A',
                    'salle'     => $row->salle      ?? 'N/A',
                    'heure'     => $heure,
                ];
            })->toArray();

        $total   = count($rows);
        $present = count(array_filter($rows, fn($r) => $r->status === 'present'));
        $late    = count(array_filter($rows, fn($r) => $r->status === 'present' && !$r->on_time));
        $absent  = $total - $present;

        // Méta pour l'en-tête des documents
        $firstRow = count($rows) > 0 ? $rows[0] : null;
        $meta = [
            'matiere' => $firstRow?->matiere ?? ($filters['matiere'] ?? 'N/A'),
            'filiere' => $firstRow?->filiere ?? ($filters['filiere'] ?? 'N/A'),
            'niveau'  => $firstRow?->niveau  ?? ($filters['niveau']  ?? 'N/A'),
            'annee'   => $firstRow?->annee   ?? 'N/A',
            'date'    => !empty($filters['date'])
                ? \Carbon\Carbon::parse($filters['date'])->translatedFormat('l d F Y')
                : now()->translatedFormat('l d F Y'),
            'heure'   => $firstRow?->heure ?? 'N/A',
            'salle'   => $firstRow?->salle ?? 'N/A',
        ];

        return [
            'students' => $rows,
            'summary'  => compact('total', 'present', 'absent', 'late'),
            'meta'     => $meta,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DONNÉES MANAGEMENT (export global)
    // ─────────────────────────────────────────────────────────────────────────

    public function getData(array $filters): array
    {
        $filters   = $this->normalizeFilters($filters);
        $dayLabels = [
            'monday'    => 'Lundi',    'tuesday'  => 'Mardi',
            'wednesday' => 'Mercredi', 'thursday' => 'Jeudi',
            'friday'    => 'Vendredi', 'saturday' => 'Samedi',
            'sunday'    => 'Dimanche',
        ];

        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('departments', 'students.filiere_id', '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'attendances.room_id', '=', 'rooms.id')
            ->leftJoin('emploi_du_temps', function ($join) {
                $join->on('emploi_du_temps.room_id', '=', 'attendances.room_id')
                     ->whereRaw("emploi_du_temps.day_of_week = LOWER(DAYNAME(attendances.date))")
                     ->where('emploi_du_temps.is_cancelled', 0)
                     ->where('emploi_du_temps.is_active', 1);
            })
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'attendances.status',
                'attendances.date',
                'course_elements.name as matiere',
                'students.niveau',
                'departments.name as filiere',
                'academic_years.academic_year as annee',
                'rooms.name as salle',
                'emploi_du_temps.day_of_week as edt_day',
                'emploi_du_temps.start_time as edt_start',
                'emploi_du_temps.end_time as edt_end',
            );

        $annee = $filters['year'] ?? $filters['annee'] ?? null;
        if (!empty($annee))              $query->where('academic_years.academic_year', $annee);
        if (!empty($filters['filiere'])) $query->where('departments.name', $filters['filiere']);
        if (!empty($filters['niveau']))  $query->where('students.niveau', $filters['niveau']);
        if (!empty($filters['matiere'])) $query->where('course_elements.name', 'like', '%'.$filters['matiere'].'%');
        if (!empty($filters['heure'])) {
            $p = $this->parseHeure($filters['heure']);
            if ($p) {
                $query->where('emploi_du_temps.day_of_week', $p['day_of_week'])
                      ->whereRaw("TIME_FORMAT(emploi_du_temps.start_time,'%H:%i') = ?", [$p['start_time']])
                      ->whereRaw("TIME_FORMAT(emploi_du_temps.end_time,'%H:%i') = ?",   [$p['end_time']]);
            }
        }

        return $query->orderBy('attendances.date', 'desc')->orderBy('students.last_name')
            ->limit(500)->get()
            ->map(function ($row) use ($dayLabels) {
                $heure = null;
                if (!empty($row->edt_day)) {
                    $day   = $dayLabels[$row->edt_day] ?? ucfirst($row->edt_day);
                    $start = substr($row->edt_start ?? '', 0, 5);
                    $end   = substr($row->edt_end   ?? '', 0, 5);
                    $heure = "{$day} {$start} - {$end}";
                }
                return (object)[
                    'name'      => $row->name      ?? 'N/A',
                    'matricule' => $row->matricule  ?? 'N/A',
                    'phone'     => $row->phone      ?? null,
                    'status'    => $row->status     ?? 'absent',
                    'date'      => $row->date       ?? '',
                    'matiere'   => $row->matiere    ?? 'N/A',
                    'niveau'    => $row->niveau     ?? 'N/A',
                    'filiere'   => $row->filiere    ?? 'N/A',
                    'annee'     => $row->annee      ?? 'N/A',
                    'salle'     => $row->salle      ?? 'N/A',
                    'heure'     => $heure,
                ];
            })->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DONNÉES FINGERPRINT
    // ─────────────────────────────────────────────────────────────────────────

    public function getFingerprintData(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = DB::table('students')
            ->join('departments', 'students.filiere_id', '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'students.fingerprint_status as fingerprint',
                'students.niveau',
                'departments.name as filiere',
                'academic_years.academic_year as annee'
            );

        $annee = $filters['annee'] ?? $filters['year'] ?? null;
        if (!empty($annee))              $query->where('academic_years.academic_year', $annee);
        if (!empty($filters['filiere'])) $query->where('departments.name', $filters['filiere']);
        if (!empty($filters['niveau']))  $query->where('students.niveau', $filters['niveau']);

        return $query->orderBy('students.last_name')->get()
            ->map(fn($row) => (object)[
                'name'        => $row->name      ?? 'N/A',
                'matricule'   => $row->matricule  ?? 'N/A',
                'phone'       => $row->phone      ?? null,
                'fingerprint' => (bool) $row->fingerprint,
                'niveau'      => $row->niveau     ?? 'N/A',
                'filiere'     => $row->filiere    ?? 'N/A',
                'annee'       => $row->annee      ?? 'N/A',
            ])->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  EXPORTS MANAGEMENT (global)
    // ─────────────────────────────────────────────────────────────────────────

    public function exportPdf(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getData($filters);
        $pages    = array_chunk($students, 20);
        return $this->pdfService->downloadPdf(
            'attendance::exports.pdf',
            ['pages' => $pages, 'filters' => $filters, 'date' => now()->format('d/m/Y H:i'), 'total' => count($students)],
            'liste_presence_' . now()->format('Ymd_His') . '.pdf',
            ['orientation' => 'landscape', 'paper' => 'a4']
        );
    }

    public function exportExcel(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getData($filters);
        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($students, 'management', $filters),
            'liste_presence_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportWord(array $filters)
    {
        $filters = $this->normalizeFilters($filters);
        return $this->buildWord($this->getData($filters), $filters, 'management');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  EXPORTS PAR SÉANCE / COURS  ← NOUVEAUX
    // ─────────────────────────────────────────────────────────────────────────

    public function exportCoursePdf(array $filters)
    {
        $data = $this->getCourseExportData($filters);
        return $this->pdfService->downloadPdf(
            'attendance::exports.course_pdf',
            [
                'students' => $data['students'],
                'summary'  => $data['summary'],
                'meta'     => $data['meta'],
                'date'     => now()->format('d/m/Y H:i'),
            ],
            'fiche_seance_' . now()->format('Ymd_His') . '.pdf',
            ['orientation' => 'portrait', 'paper' => 'a4']
        );
    }

    public function exportCourseExcel(array $filters)
    {
        $data = $this->getCourseExportData($filters);
        // On enrichit les filtres avec les méta pour l'en-tête Excel
        $enrichedFilters = array_merge($filters, [
            'matiere' => $data['meta']['matiere'],
            'filiere' => $data['meta']['filiere'],
            'niveau'  => $data['meta']['niveau'],
            'annee'   => $data['meta']['annee'],
            'date'    => $data['meta']['date'],
            'heure'   => $data['meta']['heure'],
            'salle'   => $data['meta']['salle'],
            'summary' => $data['summary'],
        ]);
        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($data['students'], 'course', $enrichedFilters),
            'fiche_seance_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportCourseWord(array $filters)
    {
        $data = $this->getCourseExportData($filters);
        $enrichedFilters = array_merge($filters, $data['meta'], ['summary' => $data['summary']]);
        return $this->buildWord($data['students'], $enrichedFilters, 'course');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  EXPORTS FINGERPRINT
    // ─────────────────────────────────────────────────────────────────────────

    public function exportFingerprintPdf(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getFingerprintData($filters);
        return $this->pdfService->downloadPdf(
            'attendance::exports.fingerprint_pdf',
            ['students' => $students, 'filters' => $filters, 'date' => now()->format('d/m/Y H:i'), 'total' => count($students)],
            'empreintes_' . now()->format('Ymd_His') . '.pdf',
            ['orientation' => 'portrait']
        );
    }

    public function exportFingerprintExcel(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getFingerprintData($filters);
        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($students, 'fingerprint', $filters),
            'empreintes_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportFingerprintWord(array $filters)
    {
        $filters = $this->normalizeFilters($filters);
        return $this->buildWord($this->getFingerprintData($filters), $filters, 'fingerprint');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BUILD WORD — gère management, course ET fingerprint
    // ─────────────────────────────────────────────────────────────────────────

    private function buildWord(array $students, array $filters, string $type): mixed
    {
        $isManagement = ($type === 'management');
        $isCourse     = ($type === 'course');
        $isFingerprint= ($type === 'fingerprint');

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $margin   = 720;
        $section  = $phpWord->addSection([
            'paperSize'    => 'A4',
            'orientation'  => $isManagement ? 'landscape' : 'portrait',
            'marginTop'    => $margin, 'marginBottom' => $margin,
            'marginLeft'   => $margin, 'marginRight'  => $margin,
        ]);

        $pageWidth = $isManagement ? 15398 : 10466;

        // ── EN-TÊTE ──
        $logoW   = (int)($pageWidth * 0.13);
        $centerW = $pageWidth - 2 * $logoW;

        $hTbl = $section->addTable(['width' => $pageWidth, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 60]);
        $hTbl->addRow(900);

        $hTbl->addCell($logoW, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'center'])
             ->addText('EPAC', ['bold' => true, 'size' => 9, 'color' => '003087'], ['alignment' => 'center']);

        $cC = $hTbl->addCell($centerW, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'center']);
        $cC->addText("Universite d'Abomey-Calavi",       ['bold' => true, 'size' => 9],  ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('-=-=-=-=-=-=-',                     ['size' => 7, 'color' => '666666'], ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText("ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI", ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('-=-=-=-=-=-=-',                     ['size' => 7, 'color' => '666666'], ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('CENTRE AUTONOME DE PERFECTIONNEMENT', ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('01 BP 2009 COTONOU - TEL. 21 36 14 32 - Email: epac.uac@epac.uac.bj', ['size' => 7, 'color' => '555555'], ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);

        $hTbl->addCell($logoW, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'center'])
             ->addText('CAP', ['bold' => true, 'size' => 9, 'color' => '003087'], ['alignment' => 'center']);

        // Séparateur
        $sepTbl = $section->addTable(['width' => $pageWidth, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 60]);
        $sepTbl->addRow(1);
        $sepTbl->addCell($pageWidth, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'borderBottomSize' => 12, 'borderBottomColor' => '000000'])->addText('');

        // ── TITRE ──
        $section->addText('', [], ['spaceAfter' => 80]);
        $annee    = $filters['annee'] ?? $filters['year'] ?? '';
        $docTitle = $isManagement ? 'FICHE DE PRESENCE' : ($isCourse ? 'FICHE DE PRESENCE PAR SEANCE' : 'LISTE DES EMPREINTES DIGITALES');

        if ($annee) {
            $section->addText("Annee academique : $annee", ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 40, 'spaceBefore' => 0]);
        }
        $section->addText($docTitle, ['bold' => true, 'size' => 13], ['alignment' => 'center', 'spaceAfter' => 100, 'spaceBefore' => 0]);

        // ── INFOS DOCUMENT ──
        $filiere = $filters['filiere'] ?? '..........................................';
        $niveau  = $filters['niveau']  ?? '....................';
        $halfW   = (int)($pageWidth / 2);

        $iTbl = $section->addTable(['width' => $pageWidth, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50]);

        $iTbl->addRow(300);
        $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Filiere : ' . $filiere, ['size' => 9]);
        $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Niveau : ' . $niveau, ['size' => 9]);

        if ($isManagement) {
            $matiere = $filters['matiere'] ?? '..........................................';
            $heure   = $filters['heure']   ?? '....................';
            $iTbl->addRow(300);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Matiere : ' . $matiere, ['size' => 9]);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Heure : ' . $heure, ['size' => 9]);
        }

        if ($isCourse) {
            $matiere = $filters['matiere'] ?? '..........................................';
            $date    = $filters['date']    ?? now()->format('d/m/Y');
            $heure   = $filters['heure']   ?? '....................';
            $salle   = $filters['salle']   ?? '....................';

            $iTbl->addRow(300);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Matiere : ' . $matiere, ['size' => 9]);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Date : ' . $date, ['size' => 9]);

            $iTbl->addRow(300);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Creneau : ' . $heure, ['size' => 9]);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Salle : ' . $salle, ['size' => 9]);

            // Résumé séance
            $summary = $filters['summary'] ?? [];
            if (!empty($summary)) {
                $section->addText('', [], ['spaceAfter' => 40]);
                $sTbl = $section->addTable(['width' => $pageWidth, 'borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 60]);
                $thirdW = (int)($pageWidth / 4);
                $sTbl->addRow(350);
                foreach ([
                    ['Effectif', $summary['total']   ?? 0, 'EEEEEE', '333333'],
                    ['Presents', $summary['present'] ?? 0, 'D4EDDA', '155724'],
                    ['Retards',  $summary['late']    ?? 0, 'FFF3CD', '856404'],
                    ['Absents',  $summary['absent']  ?? 0, 'F8D7DA', '721C24'],
                ] as [$lbl, $val, $bg, $color]) {
                    $cell = $sTbl->addCell($thirdW, ['bgColor' => $bg]);
                    $cell->addText((string)$val, ['bold' => true, 'size' => 14, 'color' => $color], ['alignment' => 'center', 'spaceAfter' => 0]);
                    $cell->addText($lbl, ['size' => 8, 'color' => $color], ['alignment' => 'center', 'spaceAfter' => 0]);
                }
            }
        }

        if ($isFingerprint) {
            $iTbl->addRow(300);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Date : ' . now()->format('d/m/Y'), ['size' => 9]);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('', ['size' => 9]);
        }

        $section->addText("Enseignant : ...............................................................................................", ['size' => 9], ['spaceAfter' => 80, 'spaceBefore' => 60]);

        // ── TABLEAU DE DONNÉES ──
        if ($isManagement) {
            $headers = ['N', 'Matricule', 'Noms et Prenoms', 'Contact', 'Matiere', 'Date', 'Creneau', 'Statut'];
            $widths  = [
                (int)($pageWidth * 0.04), (int)($pageWidth * 0.09), (int)($pageWidth * 0.18),
                (int)($pageWidth * 0.12), (int)($pageWidth * 0.16), (int)($pageWidth * 0.09),
                (int)($pageWidth * 0.17), (int)($pageWidth * 0.10),
            ];
        } elseif ($isCourse) {
            $headers = ['N', 'Matricule', 'Noms et Prenoms', 'Contact', 'Statut', 'Signature'];
            $widths  = [
                (int)($pageWidth * 0.05), (int)($pageWidth * 0.13), (int)($pageWidth * 0.30),
                (int)($pageWidth * 0.17), (int)($pageWidth * 0.15), (int)($pageWidth * 0.20),
            ];
        } else {
            $headers = ['N', 'Matricule', 'Noms et Prenoms', 'Contact', 'Niveau', 'Empreinte digitale'];
            $widths  = [
                (int)($pageWidth * 0.05), (int)($pageWidth * 0.13), (int)($pageWidth * 0.30),
                (int)($pageWidth * 0.20), (int)($pageWidth * 0.10), (int)($pageWidth * 0.22),
            ];
        }

        $dataTable = $section->addTable(['width' => $pageWidth, 'borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]);
        $dataTable->addRow(380);
        foreach ($headers as $i => $h) {
            $dataTable->addCell($widths[$i], ['bgColor' => 'E0E0E0'])
                      ->addText($h, ['bold' => true, 'size' => 8], ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        }

        foreach ($students as $idx => $s) {
            $bg = ($idx % 2 === 0) ? 'FFFFFF' : 'F5F5F5';
            $cs = ['bgColor' => $bg];
            $dataTable->addRow(320);

            $dataTable->addCell($widths[0], $cs)->addText((string)($idx + 1), ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
            $dataTable->addCell($widths[1], $cs)->addText($s->matricule ?? 'N/A', ['size' => 8, 'bold' => true], ['spaceAfter' => 0]);
            $dataTable->addCell($widths[2], $cs)->addText($s->name ?? 'N/A', ['size' => 8], ['spaceAfter' => 0]);
            $dataTable->addCell($widths[3], $cs)->addText($s->phone ?? '-', ['size' => 8], ['spaceAfter' => 0]);

            if ($isManagement) {
                $isPresent  = ($s->status ?? '') === 'present';
                $statusText = $isPresent ? 'Present' : 'Absent';
                $color      = $isPresent ? '008000' : 'CC0000';
                $dataTable->addCell($widths[4], $cs)->addText($s->matiere ?? 'N/A', ['size' => 8], ['spaceAfter' => 0]);
                $dataTable->addCell($widths[5], $cs)->addText($s->date ?? '',        ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[6], $cs)->addText($s->heure ?? '-',     ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[7], $cs)->addText($statusText, ['size' => 8, 'bold' => true, 'color' => $color], ['alignment' => 'center', 'spaceAfter' => 0]);
            } elseif ($isCourse) {
                $isPresent = ($s->status ?? '') === 'present';
                $isLate    = $isPresent && !($s->on_time ?? true);
                $statusText= $isLate ? 'Present (retard)' : ($isPresent ? 'Present' : 'Absent');
                $color     = $isLate ? 'D97706' : ($isPresent ? '008000' : 'CC0000');
                $dataTable->addCell($widths[4], $cs)->addText($statusText, ['size' => 8, 'bold' => true, 'color' => $color], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[5], $cs)->addText('', ['size' => 8], ['spaceAfter' => 0]); // colonne signature vide
            } else {
                $hasFp   = ($s->fingerprint ?? false);
                $fpText  = $hasFp ? 'Enregistree' : 'Non enregistree';
                $fpColor = $hasFp ? '008000' : 'CC0000';
                $dataTable->addCell($widths[4], $cs)->addText($s->niveau ?? 'N/A', ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[5], $cs)->addText($fpText, ['size' => 8, 'bold' => true, 'color' => $fpColor], ['alignment' => 'center', 'spaceAfter' => 0]);
            }
        }

        // ── PIED DE PAGE ──
        $section->addText('', [], ['spaceAfter' => 160]);

        if ($isManagement || $isCourse) {
            $presents = count(array_filter($students, fn($s) => ($s->status ?? '') === 'present'));
            $absents  = count($students) - $presents;
            $third    = (int)($pageWidth / 3);
            $fTbl     = $section->addTable(['width' => $pageWidth, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50]);
            $fTbl->addRow(320);
            $fTbl->addCell($third, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Effectif : ' . count($students), ['size' => 9, 'bold' => true]);
            $fTbl->addCell($third, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Presents : ' . $presents, ['size' => 9]);
            $fTbl->addCell($third, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText("Absents : $absents", ['size' => 9]);
        }

        $section->addText('', [], ['spaceAfter' => 600]);
        $half   = (int)($pageWidth / 2);
        $sigTbl = $section->addTable(['width' => $pageWidth, 'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50]);
        $sigTbl->addRow(320);
        $sigTbl->addCell($half, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText('Signature et Nom des surveillants :', ['bold' => true, 'size' => 9]);
        $sigTbl->addCell($half, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])->addText("Signature et Nom de l'Enseignant :", ['bold' => true, 'size' => 9], ['alignment' => 'right']);

        $section->addText('', [], ['spaceAfter' => 400]);
        $section->addText('Imprime le ' . now()->format('d/m/Y a H:i') . ' par le systeme', ['size' => 7, 'color' => '888888'], ['alignment' => 'right']);

        // ── ÉCRITURE FICHIER ──
        $prefix   = $isCourse ? 'fiche_seance' : ($isManagement ? 'fiche_presence' : 'empreintes');
        $filename = $prefix . '_' . now()->format('Ymd_His') . '.docx';
        $tempDir  = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}

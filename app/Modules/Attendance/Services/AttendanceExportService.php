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

    // =========================================================
    // PARSING HEURE
    // Format : "Lundi 08:00 - 12:00"
    // =========================================================
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

    // =========================================================
    // NORMALISER LES FILTRES
    // Le frontend peut envoyer 'year' ou 'annee' selon le contexte.
    // On normalise pour que getData() et le PDF utilisent
    // toujours la meme cle.
    // =========================================================
    private function normalizeFilters(array $filters): array
    {
        // 'year' et 'annee' sont synonymes -> on garde les deux
        if (!empty($filters['year']) && empty($filters['annee'])) {
            $filters['annee'] = $filters['year'];
        }
        if (!empty($filters['annee']) && empty($filters['year'])) {
            $filters['year'] = $filters['annee'];
        }
        return $filters;
    }

    // =========================================================
    // DONNEES MANAGEMENT
    // =========================================================
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

        // ✅ Filtre annee : accepte 'year' ET 'annee'
        $annee = $filters['year'] ?? $filters['annee'] ?? null;
        if (!empty($annee)) {
            $query->where('academic_years.academic_year', $annee);
        }
        if (!empty($filters['filiere'])) {
            $query->where('departments.name', $filters['filiere']);
        }
        if (!empty($filters['niveau'])) {
            $query->where('students.niveau', $filters['niveau']);
        }
        if (!empty($filters['matiere'])) {
            $query->where('course_elements.name', 'like', '%' . $filters['matiere'] . '%');
        }
        if (!empty($filters['heure'])) {
            $p = $this->parseHeure($filters['heure']);
            if ($p) {
                $query->where('emploi_du_temps.day_of_week', $p['day_of_week'])
                      ->whereRaw("TIME_FORMAT(emploi_du_temps.start_time,'%H:%i') = ?", [$p['start_time']])
                      ->whereRaw("TIME_FORMAT(emploi_du_temps.end_time,'%H:%i') = ?",   [$p['end_time']]);
            }
        }

        return $query
            ->orderBy('attendances.date', 'desc')
            ->orderBy('students.last_name')
            ->limit(500)
            ->get()
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
            })
            ->toArray();
    }

    // =========================================================
    // DONNEES FINGERPRINT
    // =========================================================
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
        if (!empty($annee))          $query->where('academic_years.academic_year', $annee);
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

    // =========================================================
    // EXPORT PDF MANAGEMENT
    // =========================================================
// EXPORT PDF MANAGEMENT
// =========================================================
public function exportPdf(array $filters)
{
    $filters  = $this->normalizeFilters($filters);
    $students = $this->getData($filters);

    // Pagination pour optimiser Dompdf
    $perPage = 20;
    $pages = array_chunk($students, $perPage);

    return $this->pdfService->downloadPdf(
        'attendance::exports.pdf',
        [
            'pages'   => $pages,
            'filters' => $filters,
            'date'    => now()->format('d/m/Y H:i'),
            'total'   => count($students),
        ],
        'liste_presence_' . now()->format('Ymd_His') . '.pdf',
        [
            'orientation' => 'landscape',
            'paper' => 'a4'
        ]
    );
}
    // =========================================================
    // EXPORT PDF FINGERPRINT
    // =========================================================
    public function exportFingerprintPdf(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getFingerprintData($filters);

        return $this->pdfService->downloadPdf(
            'attendance::exports.fingerprint_pdf',
            [
                'students' => $students,
                'filters'  => $filters,
                'date'     => now()->format('d/m/Y H:i'),
                'total'    => count($students),
            ],
            'empreintes_' . now()->format('Ymd_His') . '.pdf',
            ['orientation' => 'portrait']
        );
    }

    // =========================================================
    // EXPORT EXCEL MANAGEMENT
    // =========================================================
    public function exportExcel(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getData($filters);
        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($students, 'management', $filters),
            'liste_presence_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // =========================================================
    // EXPORT EXCEL FINGERPRINT
    // =========================================================
    public function exportFingerprintExcel(array $filters)
    {
        $filters  = $this->normalizeFilters($filters);
        $students = $this->getFingerprintData($filters);
        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($students, 'fingerprint', $filters),
            'empreintes_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // =========================================================
    // EXPORT WORD MANAGEMENT
    // =========================================================
    public function exportWord(array $filters)
    {
        $filters = $this->normalizeFilters($filters);
        return $this->buildWord($this->getData($filters), $filters, 'management');
    }

    // =========================================================
    // EXPORT WORD FINGERPRINT
    // =========================================================
    public function exportFingerprintWord(array $filters)
    {
        $filters = $this->normalizeFilters($filters);
        return $this->buildWord($this->getFingerprintData($filters), $filters, 'fingerprint');
    }

    // =========================================================
    // BUILD WORD
    // Format A4 propre, sans addShape (cause du trait deplace),
    // logos alignes, marges reduites pour management
    // =========================================================
    private function buildWord(array $students, array $filters, string $type): mixed
    {
        $isManagement = ($type === 'management');

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        // ── Dimensions A4 en twips (1 cm = 567 twips) ─────────────
        // A4 portrait  : 11906 x 16838
        // A4 landscape : 16838 x 11906
        // Marges : 1 cm = 567 twips
        $margin = 720; // ~1.27 cm

        $section = $phpWord->addSection([
            'paperSize'    => 'A4',
            'orientation'  => $isManagement ? 'landscape' : 'portrait',
            'marginTop'    => $margin,
            'marginBottom' => $margin,
            'marginLeft'   => $margin,
            'marginRight'  => $margin,
        ]);

        // Largeur utile en twips
        // A4 portrait  : 11906 - 2*720 = 10466
        // A4 landscape : 16838 - 2*720 = 15398
        $pageWidth = $isManagement ? 15398 : 10466;

        // ── EN-TETE EPAC/CAP ──────────────────────────────────────
        // Tableau 3 colonnes sans bordure : EPAC | texte | CAP
        // IMPORTANT : on n'utilise PAS addShape (cause le trait deplace)
        $logoW   = (int)($pageWidth * 0.13);
        $centerW = $pageWidth - 2 * $logoW;

        $tblStyle = [
            'width'       => $pageWidth,
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 60,
        ];

        $hTbl = $section->addTable($tblStyle);
        $hTbl->addRow(900);

        // Col gauche
        $cL = $hTbl->addCell($logoW, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'center']);
        $cL->addText('EPAC', ['bold' => true, 'size' => 9, 'color' => '003087'], ['alignment' => 'center']);

        // Col centre
        $cC = $hTbl->addCell($centerW, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'center']);
        $cC->addText("Universite d'Abomey-Calavi",
            ['bold' => true, 'size' => 9],
            ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('-=-=-=-=-=-=-',
            ['size' => 7, 'color' => '666666'],
            ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText("ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI",
            ['bold' => true, 'size' => 11],
            ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('-=-=-=-=-=-=-',
            ['size' => 7, 'color' => '666666'],
            ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('CENTRE AUTONOME DE PERFECTIONNEMENT',
            ['bold' => true, 'size' => 10],
            ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cC->addText('01 BP 2009 COTONOU - TEL. 21 36 14 32/21 36 09 93 - Email: epac.uac@epac.uac.bj',
            ['size' => 7, 'color' => '555555'],
            ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);

        // Col droite
        $cR = $hTbl->addCell($logoW, ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'valign' => 'center']);
        $cR->addText('CAP', ['bold' => true, 'size' => 9, 'color' => '003087'], ['alignment' => 'center']);

        // ── SEPARATEUR HORIZONTAL ──────────────────────────────────
        // On utilise une bordure basse sur un paragraphe dans un tableau
        // d'une seule cellule. C'est la methode correcte dans PhpWord
        // pour eviter le trait qui se deplace avec addShape.
        $sepTbl = $section->addTable([
            
            'width'       => $pageWidth,
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 60,
        ]);
        $sepTbl->addRow(1);
        $sepTbl->addCell($pageWidth, [
            'borderSize'        => 0,
            'borderColor'       => 'FFFFFF',
            'borderBottomSize'  => 12,
            'borderBottomColor' => '000000',
        ])->addText('');

        // ── TITRE ─────────────────────────────────────────────────
        $annee    = $filters['year'] ?? $filters['annee'] ?? '';
        $docTitle = $isManagement ? 'FICHE DE PRESENCE' : 'LISTE DES EMPREINTES DIGITALES';

        $section->addText('', [], ['spaceAfter' => 80]);

        if ($annee) {
            $section->addText("Annee academique : $annee",
                ['bold' => true, 'size' => 10],
                ['alignment' => 'center', 'spaceAfter' => 40, 'spaceBefore' => 0]);
        }
        $section->addText($docTitle,
            ['bold' => true, 'size' => 13],
            ['alignment' => 'center', 'spaceAfter' => 100, 'spaceBefore' => 0]);

        // ── INFOS DOCUMENT ────────────────────────────────────────
        $filiere = !empty($filters['filiere']) ? $filters['filiere'] : '..........................................';
        $matiere = !empty($filters['matiere']) ? $filters['matiere'] : '..........................................';
        $niveau  = !empty($filters['niveau'])  ? $filters['niveau']  : '....................';
        $heure   = !empty($filters['heure'])   ? $filters['heure']   : '....................';

        $halfW = (int)($pageWidth / 2);

        $iTbl = $section->addTable([
            'width' => $pageWidth,
            'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50,
        ]);

        $iTbl->addRow(300);
        $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
             ->addText('Filiere : ' . $filiere, ['size' => 9]);
        $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
             ->addText('Classe : .....................     Date : .......................', ['size' => 9]);

        if ($isManagement) {
            $iTbl->addRow(300);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText('Matiere : ' . $matiere, ['size' => 9]);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText('Duree : ....................    Heure : ' . $heure, ['size' => 9]);
        } else {
            $iTbl->addRow(300);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText('Niveau : ' . $niveau, ['size' => 9]);
            $iTbl->addCell($halfW, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText('Date : ' . now()->format('d/m/Y'), ['size' => 9]);
        }

        $section->addText(
            'Enseignant : ...............................................................................................',
            ['size' => 9],
            ['spaceAfter' => 80, 'spaceBefore' => 60]
        );

        // ── TABLEAU DES DONNEES ────────────────────────────────────
        if ($isManagement) {
            $headers = ['N', 'Matricule', 'Noms et Prenoms', 'Contact', 'Matiere', 'Date', 'Creneau', 'Statut'];
            // Largeurs proportionnelles sur pageWidth
            $widths  = [
                (int)($pageWidth * 0.04),
                (int)($pageWidth * 0.09),
                (int)($pageWidth * 0.18),
                (int)($pageWidth * 0.12),
                (int)($pageWidth * 0.16),
                (int)($pageWidth * 0.09),
                (int)($pageWidth * 0.17),
                (int)($pageWidth * 0.10),
            ];
        } else {
            $headers = ['N', 'Matricule', 'Noms et Prenoms', 'Contact', 'Niveau', 'Empreinte digitale'];
            $widths  = [
                (int)($pageWidth * 0.05),
                (int)($pageWidth * 0.13),
                (int)($pageWidth * 0.30),
                (int)($pageWidth * 0.20),
                (int)($pageWidth * 0.10),
                (int)($pageWidth * 0.22),
            ];
        }

        $dataTable = $section->addTable([
            'width'       => $pageWidth,
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 60,
        ]);

        // En-tetes
        $dataTable->addRow(380);
        foreach ($headers as $i => $h) {
            $dataTable->addCell($widths[$i], ['bgColor' => 'E0E0E0'])
                      ->addText($h, ['bold' => true, 'size' => 8],
                                ['alignment' => 'center', 'spaceAfter' => 0, 'spaceBefore' => 0]);
        }

        // Lignes de donnees
        foreach ($students as $idx => $s) {
            $bg = ($idx % 2 === 0) ? 'FFFFFF' : 'F5F5F5';
            $cs = ['bgColor' => $bg];

            $dataTable->addRow(320);
            $dataTable->addCell($widths[0], $cs)
                ->addText((string)($idx + 1), ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
            $dataTable->addCell($widths[1], $cs)
                ->addText($s->matricule ?? 'N/A', ['size' => 8, 'bold' => true], ['spaceAfter' => 0]);
            $dataTable->addCell($widths[2], $cs)
                ->addText($s->name ?? 'N/A', ['size' => 8], ['spaceAfter' => 0]);
            $dataTable->addCell($widths[3], $cs)
                ->addText($s->phone ?? '-', ['size' => 8], ['spaceAfter' => 0]);

            if ($isManagement) {
                $isPresent   = ($s->status ?? '') === 'present';
                $statusText  = $isPresent ? 'Present' : 'Absent';
                $statusColor = $isPresent ? '008000' : 'CC0000';

                $dataTable->addCell($widths[4], $cs)
                    ->addText($s->matiere ?? 'N/A', ['size' => 8], ['spaceAfter' => 0]);
                $dataTable->addCell($widths[5], $cs)
                    ->addText($s->date ?? '', ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[6], $cs)
                    ->addText($s->heure ?? '-', ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[7], $cs)
                    ->addText($statusText, ['size' => 8, 'bold' => true, 'color' => $statusColor],
                              ['alignment' => 'center', 'spaceAfter' => 0]);
            } else {
                $hasFp   = ($s->fingerprint ?? false);
                $fpText  = $hasFp ? 'Enregistree' : 'Non enregistree';
                $fpColor = $hasFp ? '008000' : 'CC0000';

                $dataTable->addCell($widths[4], $cs)
                    ->addText($s->niveau ?? 'N/A', ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $dataTable->addCell($widths[5], $cs)
                    ->addText($fpText, ['size' => 8, 'bold' => true, 'color' => $fpColor],
                              ['alignment' => 'center', 'spaceAfter' => 0]);
            }
        }

        // ── PIED DE PAGE ──────────────────────────────────────────
        $section->addText('', [], ['spaceAfter' => 160]);

        $presents = count(array_filter($students, fn($s) => ($s->status ?? '') === 'present'));
        $absents  = count($students) - $presents;

        if ($isManagement) {
            $third = (int)($pageWidth / 3);
            $sTbl  = $section->addTable([
                
                'width' => $pageWidth,
                'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50,
            ]);
            $sTbl->addRow(320);
            $sTbl->addCell($third, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText('Effectif de la classe : ' . count($students), ['size' => 9, 'bold' => true]);
            $sTbl->addCell($third, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText('Nombre de present : ' . $presents, ['size' => 9]);
            $sTbl->addCell($third, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
                 ->addText("Nombre d'absent : " . $absents, ['size' => 9]);
        }

        // Espace avant signatures
        $section->addText('', [], ['spaceAfter' => 600]);

        // Tableau signatures
        $half   = (int)($pageWidth / 2);
        $sigTbl = $section->addTable([
            
            'width' => $pageWidth,
            'borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50,
        ]);
        $sigTbl->addRow(320);
        $sigTbl->addCell($half, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
               ->addText('Signature et Nom des surveillants :', ['bold' => true, 'size' => 9]);
        $sigTbl->addCell($half, ['borderSize' => 0, 'borderColor' => 'FFFFFF'])
               ->addText("Signature et Nom de l'Enseignant :", ['bold' => true, 'size' => 9],
                         ['alignment' => 'right']);

        // Ligne imprime
        $section->addText('', [], ['spaceAfter' => 400]);
        $section->addText(
            'Imprime le ' . now()->format('d/m/Y a H:i') . ' par le systeme',
            ['size' => 7, 'color' => '888888'],
            ['alignment' => 'right']
        );

        // ── ECRITURE DU FICHIER ───────────────────────────────────
        $filename = ($isManagement ? 'fiche_presence' : 'empreintes') . '_' . now()->format('Ymd_His') . '.docx';
        $tempDir  = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}

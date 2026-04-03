<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use App\Modules\Core\Services\PdfService;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class AttendanceExportService
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    // =============================================
    // DONNÉES MANAGEMENT
    // =============================================
    public function getData(array $filters): array
    {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('filieres', 'students.filiere_id', '=', 'filieres.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'attendances.room_id', '=', 'rooms.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'attendances.status',
                'attendances.date',
                'course_elements.name as matiere',
                'students.niveau',
                'filieres.name as filiere',
                'academic_years.academic_year as annee',
                'rooms.name as salle',
            );

        if (!empty($filters['year']))    $query->where('academic_years.academic_year', $filters['year']);
        if (!empty($filters['filiere'])) $query->where('filieres.name', $filters['filiere']);
        if (!empty($filters['niveau']))  $query->where('students.niveau', $filters['niveau']);
        if (!empty($filters['matiere'])) $query->where('course_elements.name', 'like', '%' . $filters['matiere'] . '%');
        if (!empty($filters['salle']))   $query->where('rooms.name', $filters['salle']);

        return $query
            ->orderBy('students.last_name')
            ->limit(500)
            ->get()
            ->map(fn($row) => (object)[
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
            ])
            ->toArray();
    }

    // =============================================
    // DONNÉES FINGERPRINT
    // =============================================
    public function getFingerprintData(array $filters): array
    {
        $query = DB::table('students')
            ->join('filieres', 'students.filiere_id', '=', 'filieres.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'students.fingerprint_status as fingerprint',
                'students.niveau',
                'filieres.name as filiere',
                'academic_years.academic_year as annee'
            );

        if (!empty($filters['annee']))   $query->where('academic_years.academic_year', $filters['annee']);
        if (!empty($filters['filiere'])) $query->where('filieres.name', $filters['filiere']);
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
            ])
            ->toArray();
    }

    // =============================================
    // EXPORT PDF MANAGEMENT
    // Via PdfService qui utilise loadView('attendance::exports.pdf')
    // =============================================
    public function exportPdf(array $filters)
    {
        $students = $this->getData($filters);

        // ✅ Utilise le namespace 'attendance::' enregistré dans AttendanceServiceProvider
        return $this->pdfService->downloadPdf(
            'attendance::exports.pdf',
            [
                'students' => $students,
                'filters'  => $filters,
                'date'     => now()->format('d/m/Y H:i'),
                'total'    => count($students),
            ],
            'liste_presence_' . now()->format('Ymd_His') . '.pdf',
            ['orientation' => 'landscape']
        );
    }

    // =============================================
    // EXPORT PDF FINGERPRINT
    // =============================================
    public function exportFingerprintPdf(array $filters)
    {
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

    // =============================================
    // EXPORT EXCEL MANAGEMENT
    // =============================================
    public function exportExcel(array $filters)
    {
        $students = $this->getData($filters);

        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($students, 'management'),
            'liste_presence_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // =============================================
    // EXPORT EXCEL FINGERPRINT
    // =============================================
    public function exportFingerprintExcel(array $filters)
    {
        $students = $this->getFingerprintData($filters);

        return Excel::download(
            new \App\Modules\Attendance\Exports\AttendanceExport($students, 'fingerprint'),
            'empreintes_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // =============================================
    // EXPORT WORD (management + fingerprint)
    // =============================================
    public function exportWord(array $filters)
    {
        return $this->generateWord($this->getData($filters), $filters, 'management');
    }

    public function exportFingerprintWord(array $filters)
    {
        return $this->generateWord($this->getFingerprintData($filters), $filters, 'fingerprint');
    }

    private function generateWord(array $students, array $filters, string $type)
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $isManagement = $type === 'management';

        $section = $phpWord->addSection([
            'orientation'  => $isManagement ? 'landscape' : 'portrait',
            'marginTop'    => 800, 'marginBottom' => 800,
            'marginLeft'   => 800, 'marginRight'  => 800,
        ]);

        // En-tête EPAC/CAP
        $section->addText(
            'Université d\'Abomey-Calavi — ECOLE POLYTECHNIQUE D\'ABOMEY-CALAVI — CENTRE AUTONOME DE PERFECTIONNEMENT',
            ['bold' => true, 'size' => 11],
            ['alignment' => 'center']
        );
        $section->addText(
            '01 BP 2009 COTONOU - TÉL. 21 36 14 32/21 36 09 93',
            ['size' => 9, 'color' => '555555'],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );

        // Titre
        $annee    = $filters['year'] ?? $filters['annee'] ?? '';
        $docTitle = $isManagement ? 'LISTE DE PRÉSENCE' : 'LISTE DES EMPREINTES DIGITALES';
        if ($annee) {
            $section->addText("Année académique : $annee", ['bold' => true, 'size' => 11], ['alignment' => 'center']);
        }
        $section->addText($docTitle, ['bold' => true, 'size' => 14], ['alignment' => 'center', 'spaceAfter' => 200]);

        // Infos
        if (!empty($filters['filiere'])) {
            $section->addText('Filière : ' . $filters['filiere'], ['size' => 10, 'bold' => true]);
        }
        if (!empty($filters['niveau'])) {
            $section->addText('Niveau : ' . $filters['niveau'], ['size' => 10, 'bold' => true]);
        }
        if ($isManagement && !empty($filters['matiere'])) {
            $section->addText('Matière : ' . $filters['matiere'], ['size' => 10, 'bold' => true]);
        }
        $section->addText('Total : ' . count($students) . ' enregistrement(s)', ['size' => 10], ['spaceAfter' => 200]);

        // Colonnes
        if ($isManagement) {
            $headers = ['N°', 'Matricule', 'Noms et Prénoms', 'Contact', 'Matière', 'Date', 'Statut'];
            $widths  = [400, 1400, 2500, 1600, 1800, 1200, 1000];
        } else {
            $headers = ['N°', 'Matricule', 'Noms et Prénoms', 'Contact', 'Niveau', 'Empreinte'];
            $widths  = [400, 1400, 2800, 1800, 900, 1600];
        }

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);

        // En-têtes tableau
        $table->addRow(400);
        foreach ($headers as $i => $h) {
            $table->addCell($widths[$i], ['bgColor' => 'E8E8E8'])
                  ->addText($h, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        }

        // Lignes
        foreach ($students as $idx => $s) {
            $bg        = $idx % 2 === 0 ? 'FFFFFF' : 'F5F5F5';
            $cellStyle = ['bgColor' => $bg];

            $table->addRow(350);
            $table->addCell($widths[0], $cellStyle)->addText($idx + 1,     ['size' => 9], ['alignment' => 'center']);
            $table->addCell($widths[1], $cellStyle)->addText($s->matricule ?? 'N/A', ['size' => 9]);
            $table->addCell($widths[2], $cellStyle)->addText($s->name      ?? 'N/A', ['size' => 9]);
            $table->addCell($widths[3], $cellStyle)->addText($s->phone     ?? '—',   ['size' => 9]);

            if ($isManagement) {
                $statusColor = ($s->status ?? '') === 'present' ? '008000' : 'CC0000';
                $statusText  = ($s->status ?? '') === 'present' ? 'Présent' : 'Absent';
                $table->addCell($widths[4], $cellStyle)->addText($s->matiere ?? 'N/A', ['size' => 9]);
                $table->addCell($widths[5], $cellStyle)->addText($s->date    ?? '',    ['size' => 9], ['alignment' => 'center']);
                $table->addCell($widths[6], $cellStyle)->addText($statusText, ['size' => 9, 'bold' => true, 'color' => $statusColor], ['alignment' => 'center']);
            } else {
                $fpColor = ($s->fingerprint ?? false) ? '008000' : 'CC0000';
                $fpText  = ($s->fingerprint ?? false) ? 'Enregistrée' : 'Non enregistrée';
                $table->addCell($widths[4], $cellStyle)->addText($s->niveau ?? 'N/A', ['size' => 9], ['alignment' => 'center']);
                $table->addCell($widths[5], $cellStyle)->addText($fpText, ['size' => 9, 'bold' => true, 'color' => $fpColor], ['alignment' => 'center']);
            }
        }

        // Footer
        $section->addTextBreak(1);
        $section->addText(
            'Imprimé le ' . now()->format('d/m/Y à H:i') . ' par le système',
            ['size' => 8, 'color' => '888888'],
            ['alignment' => 'right']
        );

        $filename = ($isManagement ? 'liste_presence' : 'empreintes') . '_' . now()->format('Ymd_His') . '.docx';
        $tempDir  = storage_path('app/temp');
        $tempPath = $tempDir . '/' . $filename;

        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}

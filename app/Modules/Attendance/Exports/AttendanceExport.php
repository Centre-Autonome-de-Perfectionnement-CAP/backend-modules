<?php

namespace App\Modules\Attendance\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        protected array $students,
        protected string $type = 'management' // 'management' | 'fingerprint'
    ) {}

    public function title(): string
    {
        return $this->type === 'management' ? 'Liste présences' : 'Empreintes';
    }

    public function headings(): array
    {
        return $this->type === 'management'
            ? ['N°', 'Matricule', 'Nom', 'Contact', 'Matière', 'Date', 'Statut']
            : ['N°', 'Matricule', 'Nom', 'Contact', 'Niveau', 'Empreinte'];
    }

    public function array(): array
    {
        return array_map(function ($student, $index) {
            if ($this->type === 'management') {
                return [
                    $index + 1,
                    $student->matricule ?? 'N/A',
                    $student->name      ?? 'N/A',
                    $student->phone     ?? '—',
                    $student->matiere   ?? 'N/A',
                    $student->date      ?? '',
                    ($student->status ?? '') === 'present' ? 'Présent' : 'Absent',
                ];
            } else {
                return [
                    $index + 1,
                    $student->matricule  ?? 'N/A',
                    $student->name       ?? 'N/A',
                    $student->phone      ?? '—',
                    $student->niveau     ?? 'N/A',
                    ($student->fingerprint ?? false) ? 'Enregistrée' : 'Non enregistrée',
                ];
            }
        }, $this->students, array_keys($this->students));
    }

    public function columnWidths(): array
    {
        return $this->type === 'management'
            ? ['A' => 5,  'B' => 15, 'C' => 25, 'D' => 18, 'E' => 20, 'F' => 15, 'G' => 14]
            : ['A' => 5,  'B' => 15, 'C' => 25, 'D' => 18, 'E' => 10, 'F' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        $cols    = $this->type === 'management' ? 'G' : 'F';
        $lastRow = count($this->students) + 1;

        // En-tête
        $headerColor = $this->type === 'management' ? '4472C4' : '6f42c1';
        $sheet->getStyle("A1:{$cols}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Bordures
        $sheet->getStyle("A1:{$cols}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Lignes alternées
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $bg = $this->type === 'management' ? 'F0F4FF' : 'FAF5FF';
                $sheet->getStyle("A{$row}:{$cols}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                ]);
            }
        }

        // Colonne statut colorée
        $statusCol = $this->type === 'management' ? 'G' : 'F';
        for ($row = 2; $row <= $lastRow; $row++) {
            $val   = $sheet->getCell("{$statusCol}{$row}")->getValue();
            $color = ($val === 'Présent' || $val === 'Enregistrée') ? '008000' : 'CC0000';
            $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        return [];
    }
}

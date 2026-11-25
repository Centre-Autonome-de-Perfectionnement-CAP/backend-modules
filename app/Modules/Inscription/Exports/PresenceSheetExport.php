<?php

namespace App\Modules\Inscription\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PresenceSheetExport extends DefaultValueBinder implements FromArray, WithColumnWidths, WithCustomValueBinder, WithStyles, WithTitle
{
    private int $headerRow;
    private int $lastStudentRow;

    public function __construct(
        private array $students,
        private array $filters
    ) {}

    public function title(): string
    {
        return 'Fiche de présence';
    }

    public function array(): array
    {
        $rows = [
            ["EPAC - ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI - CENTRE AUTONOME DE PERFECTIONNEMENT"],
            ["Université d'Abomey-Calavi - 01 BP 2009 COTONOU - TÉL. 21 36 14 32/21 36 09 93"],
            [''],
            ['Année académique : ' . ($this->filters['annee'] ?? 'N/A')],
            ['FICHE DE PRÉSENCE'],
            [''],
            ['Filière : ' . ($this->filters['filiere'] ?? 'N/A'), '', '', '', 'Période :'],
            ['Classe : ' . ($this->filters['classe'] ?? 'N/A'), '', '', '', 'Date :'],
            ['Matière :', '', '', '', 'Durée :'],
            ['Enseignant :'],
            [''],
        ];

        $this->headerRow = count($rows) + 1;
        $rows[] = ['N°', 'Matricule', 'Noms et Prénoms', 'Red', 'Nationalité', 'Début', 'Fin / Dépôt'];

        foreach ($this->students as $index => $student) {
            $rows[] = [
                $index + 1,
                $student->matricule ?? 'N/A',
                $student->nomPrenoms ?? '',
                ($student->redoublant ?? 'Non') === 'Oui' ? 'R' : '',
                $student->nationality ?? '',
                '',
                '',
            ];
        }

        $this->lastStudentRow = count($rows);
        $rows[] = [''];
        $rows[] = ['Effectif de la classe : ' . count($this->students), '', '', "Nombre de présents :", '', "Nombre d'absents :"];
        $rows[] = [''];
        $rows[] = ['Signature et Nom des surveillants :', '', '', '', '', "Signature et Nom de l'Enseignant :"];
        $rows[] = [''];
        $rows[] = ['Imprimé le ' . now()->format('d/m/Y à H:i') . ' par le système'];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 18,
            'C' => 36,
            'D' => 10,
            'E' => 22,
            'F' => 18,
            'G' => 22,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A4:G4');
        $sheet->mergeCells('A5:G5');
        $sheet->freezePane('A' . ($this->headerRow + 1));

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003087']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A{$this->headerRow}:G{$this->headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003087']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        if ($this->lastStudentRow > $this->headerRow) {
            $sheet->getStyle('A' . ($this->headerRow + 1) . ":G{$this->lastStudentRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        $sheet->getPageSetup()->setOrientation('landscape');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setFitToPage(true);

        return [];
    }
}

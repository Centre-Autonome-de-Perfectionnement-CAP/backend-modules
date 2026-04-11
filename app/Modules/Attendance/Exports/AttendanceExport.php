<?php

namespace App\Modules\Attendance\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AttendanceExport implements FromArray, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    private int $dataStartRow;
    private int $lastDataRow;
    private string $lastCol;

    public function __construct(
        protected array  $students,
        protected string $type    = 'management',
        protected array  $filters = []
    ) {}

    public function title(): string
    {
        return $this->type === 'management' ? 'Fiche présences' : 'Empreintes';
    }

    // Retourne TOUT le contenu (en-tête + données)
    public function array(): array
    {
        $isManagement = $this->type === 'management';

        // ── Ligne 1 : institution (fusionnée après dans styles) ──
        $rows = [];

        // Ligne 1 : EPAC | institution | CAP
        $rows[] = ['EPAC - ECOLE POLYTECHNIQUE D\'ABOMEY-CALAVI - CENTRE AUTONOME DE PERFECTIONNEMENT'];

        // Ligne 2 : adresse
        $rows[] = ['Université d\'Abomey-Calavi — 01 BP 2009 COTONOU - TÉL. 21 36 14 32/21 36 09 93'];

        // Ligne 3 : vide
        $rows[] = [''];

        // Ligne 4 : année + titre
        $annee = $this->filters['year'] ?? $this->filters['annee'] ?? 'N/A';
        $rows[] = ["Année académique : $annee"];

        $docTitle = $isManagement ? 'FICHE DE PRÉSENCE' : 'LISTE DES EMPREINTES DIGITALES';
        $rows[] = [$docTitle];

        $rows[] = [''];

        // Ligne d'infos
        $filiere = $this->filters['filiere'] ?? '---';
        $niveau  = $this->filters['niveau']  ?? '---';
        $matiere = $this->filters['matiere'] ?? '---';
        $heure   = $this->filters['heure']   ?? '---';

        if ($isManagement) {
            $rows[] = ["Filière : $filiere", '', '', "Niveau : $niveau"];
            $rows[] = ["Matière : $matiere", '', '', "Heure : $heure"];
        } else {
            $rows[] = ["Filière : $filiere", '', '', "Niveau : $niveau"];
        }

        $rows[] = [''];

        // ── En-têtes colonnes ──
        if ($isManagement) {
            $rows[] = ['N°', 'Matricule', 'Noms et Prénoms', 'Contact', 'Matière', 'Date', 'Créneau', 'Statut'];
        } else {
            $rows[] = ['N°', 'Matricule', 'Noms et Prénoms', 'Contact', 'Niveau', 'Empreinte digitale'];
        }

        $this->dataStartRow = count($rows); // ligne Excel où commencent les données (après en-têtes)

        // ── Données ──
        foreach ($this->students as $idx => $s) {
            if ($isManagement) {
                $statusText = ($s->status ?? '') === 'present' ? 'Présent' : 'Absent';
                $rows[] = [
                    $idx + 1,
                    $s->matricule ?? 'N/A',
                    $s->name      ?? 'N/A',
                    $s->phone     ?? '—',
                    $s->matiere   ?? 'N/A',
                    $s->date      ?? '',
                    $s->heure     ?? '—',
                    $statusText,
                ];
            } else {
                $rows[] = [
                    $idx + 1,
                    $s->matricule  ?? 'N/A',
                    $s->name       ?? 'N/A',
                    $s->phone      ?? '—',
                    $s->niveau     ?? 'N/A',
                    ($s->fingerprint ?? false) ? 'Enregistrée' : 'Non enregistrée',
                ];
            }
        }

        $this->lastDataRow = count($rows);

        // ── Pied de page ──
        $rows[] = [''];

        $presents = count(array_filter($this->students, fn($s) => ($s->status ?? '') === 'present'));
        $absents  = count($this->students) - $presents;

        if ($isManagement) {
            $rows[] = [
                'Effectif : ' . count($this->students),
                '',
                'Présents : ' . $presents,
                '',
                "Absents : $absents",
            ];
        }

        $rows[] = [''];
        $rows[] = ['Signature et Nom des surveillants :', '', '', '', '', "Signature et Nom de l'Enseignant :"];
        $rows[] = [''];
        $rows[] = ['_______________________________', '', '', '', '', '______________________________'];
        $rows[] = [''];
        $rows[] = ['Imprimé le ' . now()->format('d/m/Y à H:i') . ' par le système'];

        $this->lastCol = $isManagement ? 'H' : 'F';

        return $rows;
    }

    public function columnWidths(): array
    {
        return $this->type === 'management'
            ? ['A' => 5, 'B' => 14, 'C' => 28, 'D' => 18, 'E' => 20, 'F' => 14, 'G' => 20, 'H' => 13]
            : ['A' => 5, 'B' => 14, 'C' => 28, 'D' => 18, 'E' => 10, 'F' => 22];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $this->type === 'management' ? 'H' : 'F';

        // Fusionner les lignes d'en-tête institution
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->mergeCells("A5:{$lastCol}5");

        // Styles en-tête institution
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003087']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Titre du document
        $sheet->getStyle('A4')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A5')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // En-tête colonnes (dataStartRow)
        $headerRow = $this->dataStartRow;
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003087']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        // Bordures sur les données
        $dataStart = $headerRow;
        $dataEnd   = $this->lastDataRow;
        $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataEnd}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Lignes alternées
        for ($row = $headerRow + 1; $row <= $dataEnd; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF3FF']],
                ]);
            }
        }

        // Colonne statut colorée
        $statusCol = $this->type === 'management' ? 'H' : 'F';
        for ($row = $headerRow + 1; $row <= $dataEnd; $row++) {
            $val   = $sheet->getCell("{$statusCol}{$row}")->getValue();
            $color = ($val === 'Présent' || $val === 'Enregistrée') ? '008000' : 'CC0000';
            $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => $color]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Hauteur des lignes de données
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->type === 'management' ? 'H' : 'F';

                for ($row = $this->dataStartRow + 1; $row <= $this->lastDataRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }

                // Freeze la ligne d'en-têtes
                $freezeRow = $this->dataStartRow + 1;
                $sheet->freezePane("A{$freezeRow}");
            },
        ];
    }
}

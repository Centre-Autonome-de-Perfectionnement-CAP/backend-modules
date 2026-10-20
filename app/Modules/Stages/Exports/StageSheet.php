<?php

namespace App\Modules\Stages\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Feuille Excel simple du module Stages : titre, sous-titre, en-têtes, lignes.
 *
 * Toute valeur texte est écrite comme texte (jamais interprétée comme une
 * formule : protection contre l'injection de formules, et les numéros de
 * téléphone gardent leur « + » et leurs zéros).
 */
class StageSheet implements FromArray, WithTitle, WithEvents, ShouldAutoSize, WithCustomValueBinder, WithStrictNullComparison
{
    private const HEAD_ROW = 4;

    /**
     * @param string[]               $headings
     * @param array<int, array<int, scalar|null>> $rows
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly string $title,
        private readonly string $subtitle,
        private readonly array $headings,
        private readonly array $rows,
    ) {}

    public function title(): string
    {
        return mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $this->sheetTitle), 0, 31);
    }

    public function array(): array
    {
        return [[$this->title], [$this->subtitle], [''], $this->headings, ...$this->rows];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
        } elseif (is_bool($value)) {
            $cell->setValueExplicit($value ? 'Oui' : 'Non', DataType::TYPE_STRING);
        } else {
            $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
        }

        return true;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));
                $end   = self::HEAD_ROW + max(1, count($this->rows));

                $sheet->mergeCells("A1:{$last}1");
                $sheet->mergeCells("A2:{$last}2");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('475569');

                $head = $sheet->getStyle('A' . self::HEAD_ROW . ":{$last}" . self::HEAD_ROW);
                $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('326761');

                $sheet->freezePane('A' . (self::HEAD_ROW + 1));
                if ($this->rows) {
                    $sheet->setAutoFilter('A' . self::HEAD_ROW . ":{$last}{$end}");
                }
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
            },
        ];
    }
}

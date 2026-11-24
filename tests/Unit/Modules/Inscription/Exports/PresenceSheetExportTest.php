<?php

namespace Tests\Unit\Modules\Inscription\Exports;

use App\Modules\Inscription\Exports\PresenceSheetExport;
use PHPUnit\Framework\TestCase;

class PresenceSheetExportTest extends TestCase
{
    public function test_it_formats_students_as_the_presence_roster(): void
    {
        $export = new PresenceSheetExport([
            (object) [
                'matricule' => '000123',
                'nomPrenoms' => 'Doe Jane',
                'redoublant' => 'Oui',
                'nationality' => 'Béninoise',
            ],
        ], [
            'annee' => '2025-2026',
            'filiere' => 'Génie civil',
            'classe' => 'GC-L1',
        ]);

        $rows = $export->array();

        self::assertSame('FICHE DE PRÉSENCE', $rows[4][0]);
        self::assertSame(['N°', 'Matricule', 'Noms et Prénoms', 'Red', 'Nationalité', 'Début', 'Fin / Dépôt'], $rows[11]);
        self::assertSame([1, '000123', 'Doe Jane', 'R', 'Béninoise', '', ''], $rows[12]);
        self::assertSame('Effectif de la classe : 1', $rows[14][0]);
    }
}

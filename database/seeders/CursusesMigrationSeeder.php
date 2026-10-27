<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\AcademicYear;

/**
 * Migration des cursuses (academic_paths réels) depuis l'ancienne base
 * À exécuter APRÈS StudentMigrationSeeder
 */
class CursusesMigrationSeeder extends Seeder
{
    private $sqlFile;
    private $stats = [
        'total_cursuses' => 0,
        'created_paths' => 0,
        'niveau_1' => 0,
        'niveau_2' => 0,
        'niveau_3' => 0,
        'errors' => 0,
    ];

    private $errors = [];
    private $studentIdMapping = []; // Cache pour mapper ancien ID → nouveau Student

    public function __construct()
    {
        $this->sqlFile = database_path('../u374405408_progiciel_cap (2).sql');
    }

    public function run(): void
    {
        $this->command->info('🚀 Migration des cursuses (academic_paths réels)...');
        $this->command->newLine();

        if (!file_exists($this->sqlFile)) {
            $this->command->error('❌ Fichier SQL introuvable');
            return;
        }

        // Construire le mapping des IDs
        $this->command->info('📋 Construction du mapping des IDs...');
        $this->buildStudentIdMapping();

        // Supprimer les academic_paths par défaut
        $this->command->info('🗑️  Suppression des academic_paths par défaut...');
        $deletedCount = AcademicPath::count();
        AcademicPath::truncate();
        $this->command->info("   ✓ {$deletedCount} paths par défaut supprimés");
        $this->command->newLine();

        // Extraire tous les cursuses
        $this->command->info('🔍 Extraction des cursuses depuis le SQL...');
        $cursuses = $this->extractAllCursuses();

        if (empty($cursuses)) {
            $this->command->error('❌ Aucun cursus trouvé');
            return;
        }

        $this->command->info("📊 {$cursuses['count']} cursuses trouvés");
        $this->stats['total_cursuses'] = $cursuses['count'];
        $this->command->newLine();

        $bar = $this->command->getOutput()->createProgressBar($cursuses['count']);
        $bar->start();

        foreach ($cursuses['data'] as $cursus) {
            try {
                $this->migrateCursus($cursus);
                $this->stats['created_paths']++;

                // Stats par niveau
                $niveau = (int) $cursus['niveau_etude'];
                if ($niveau >= 1 && $niveau <= 3) {
                    $this->stats['niveau_' . $niveau]++;
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->errors[] = [
                    'cursus_id' => $cursus['id'],
                    'etudiant_id' => $cursus['etudiant_id'],
                    'error' => $e->getMessage(),
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->displayStats();
    }

    private function buildStudentIdMapping(): void
    {
        // Réutiliser la même extraction que StudentMigrationSeeder
        $students = $this->extractStudentsFromSQL();

        $count = 0;
        foreach ($students as $oldStudent) {
            $oldId = (int) ($oldStudent['id'] ?? 0);
            $matricule = trim($oldStudent['matricule'] ?? '');

            if ($oldId > 0 && $matricule && $matricule !== 'NULL' && strtoupper($matricule) !== 'NULL') {
                // Chercher le Student par matricule
                $student = Student::where('student_id_number', $matricule)->first();

                if ($student) {
                    $this->studentIdMapping[$oldId] = $student->id;
                    $count++;
                }
            }
        }

        $this->command->info("   ✓ {$count} étudiants mappés (ancien_id → student_id)");
    }

    /**
     * Réutiliser l'extracteur de StudentMigrationSeeder
     */
    private function extractStudentsFromSQL(): array
    {
        $sqlContent = file_get_contents($this->sqlFile);

        $pattern = '/INSERT INTO `etudiants`.*?VALUES\s*(.*?);/s';
        preg_match_all($pattern, $sqlContent, $allMatches, PREG_SET_ORDER);

        if (empty($allMatches)) {
            return [];
        }

        $students = [];

        foreach ($allMatches as $match) {
            $insertData = $match[1];

            // Parser ligne par ligne avec gestion des parenthèses
            $depth = 0;
            $currentRow = '';
            $inString = false;
            $stringChar = '';

            for ($i = 0; $i < strlen($insertData); $i++) {
                $char = $insertData[$i];
                $prevChar = $i > 0 ? $insertData[$i - 1] : '';

                if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                    if (!$inString) {
                        $inString = true;
                        $stringChar = $char;
                    } elseif ($char === $stringChar) {
                        $inString = false;
                    }
                }

                if (!$inString) {
                    if ($char === '(') {
                        $depth++;
                    } elseif ($char === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $currentRow .= $char;
                            if (!empty(trim($currentRow))) {
                                $students[] = $this->parseStudentRow($currentRow);
                            }
                            $currentRow = '';
                            continue;
                        }
                    }
                }

                if ($depth > 0) {
                    $currentRow .= $char;
                }
            }
        }

        return $students;
    }

    private function parseStudentRow(string $row): array
    {
        // Enlever les parenthèses externes
        $row = trim($row, '() ');

        // Parser les valeurs
        $values = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $depth = 0;

        for ($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];
            $prevChar = $i > 0 ? $row[$i - 1] : '';

            if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }

            if (!$inString) {
                if ($char === '[') $depth++;
                if ($char === ']') $depth--;

                if ($char === ',' && $depth === 0) {
                    $values[] = trim($current, " '\"");
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
        }

        if ($current !== '') {
            $values[] = trim($current, " '\"");
        }

        // Mapper aux colonnes
        return [
            'id' => $values[0] ?? null,
            'nom' => $values[1] ?? null,
            'prenoms' => $values[2] ?? null,
            'contacts' => $values[3] ?? null,
            'matricule' => $values[4] ?? null,
            'date_naissance' => $values[5] ?? null,
            'lieu_de_naissance' => $values[6] ?? null,
            'pays_de_naissance' => $values[7] ?? null,
            'genre' => $values[8] ?? null,
            'email' => $values[9] ?? null,
        ];
    }

    private function extractAllCursuses(): array
    {
        $sqlContent = file_get_contents($this->sqlFile);

        // Pattern pour extraire les INSERT de cursuses
        $pattern = '/INSERT INTO `cursuses`[^V]+VALUES\s*(.+?);(?=\s*(?:INSERT|\/\*|$))/s';
        preg_match_all($pattern, $sqlContent, $matches);

        if (empty($matches[1])) {
            return ['count' => 0, 'data' => []];
        }

        $cursuses = [];

        foreach ($matches[1] as $insertData) {
            $rows = $this->extractRows($insertData);

            foreach ($rows as $row) {
                $values = $this->parseRow($row);
                if ($values) {
                    $cursuses[] = $values;
                }
            }
        }

        return ['count' => count($cursuses), 'data' => $cursuses];
    }

    private function extractRows(string $data): array
    {
        $rows = [];
        $currentRow = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $char = $data[$i];
            $prevChar = $i > 0 ? $data[$i - 1] : '';

            if (($char === "'" || $char === '"') && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }

            if (!$inString) {
                if ($char === '(') {
                    $depth++;
                    if ($depth === 1) {
                        $currentRow = '';
                        continue;
                    }
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        if (!empty(trim($currentRow))) {
                            $rows[] = $currentRow;
                        }
                        $currentRow = '';
                        continue;
                    }
                }
            }

            if ($depth > 0) {
                $currentRow .= $char;
            }
        }

        return $rows;
    }

    private function parseRow(string $row): ?array
    {
        // Format: id, etudiant_id, 'annee', niveau, 'decision', created, updated
        $values = str_getcsv($row, ',', "'");

        if (count($values) < 4) {
            return null;
        }

        return [
            'id' => (int) trim($values[0]),
            'etudiant_id' => (int) trim($values[1]),
            'annee_academique' => trim($values[2] ?? '', "'"),
            'niveau_etude' => (int) trim($values[3]),
            'decision_annee' => !empty($values[4]) && strtoupper(trim($values[4], "'")) !== 'NULL' ? trim($values[4], "'") : null,
        ];
    }

    private function migrateCursus(array $cursus): void
    {
        // Trouver le nouveau student_id
        $newStudentId = $this->studentIdMapping[$cursus['etudiant_id']] ?? null;

        if (!$newStudentId) {
            throw new \Exception("Student introuvable pour ancien etudiant_id {$cursus['etudiant_id']}");
        }

        // Récupérer le lien student_pending_student
        $link = StudentPendingStudent::where('student_id', $newStudentId)->first();

        if (!$link) {
            throw new \Exception("Lien pivot introuvable pour student_id {$newStudentId}");
        }

        // Trouver l'année académique
        $academicYear = AcademicYear::where('academic_year', $cursus['annee_academique'])->first();

        if (!$academicYear) {
            // Utiliser l'année académique du pending_student par défaut
            $academicYear = $link->pendingStudent->academicYear;
        }

        // Mapper la décision (enum: pass, fail, repeat)
        $yearDecision = null;
        if ($cursus['decision_annee'] === 'admis') {
            $yearDecision = 'pass';
        } elseif ($cursus['decision_annee'] === 'double') {
            $yearDecision = 'repeat';
        } elseif ($cursus['decision_annee'] === 'enjambe') {
            $yearDecision = 'repeat'; // Triplant = repeat aussi
        }

        // Créer l'academic_path
        AcademicPath::create([
            'student_pending_student_id' => $link->id,
            'academic_year_id' => $academicYear->id,
            'study_level' => (string) $cursus['niveau_etude'],
            'year_decision' => $yearDecision,
            'financial_status' => 'Non exonéré',
            'cohort' => substr($cursus['annee_academique'], 0, 4),
        ]);
    }

    private function displayStats(): void
    {
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║           STATISTIQUES MIGRATION CURSUSES                     ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        $this->command->info("📊 Total cursuses: {$this->stats['total_cursuses']}");
        $this->command->info("   ✅ Academic paths créés: {$this->stats['created_paths']}");
        $this->command->newLine();

        $this->command->info("Répartition par niveau:");
        $this->command->info("   • Niveau 1: {$this->stats['niveau_1']}");
        $this->command->info("   • Niveau 2: {$this->stats['niveau_2']}");
        $this->command->info("   • Niveau 3: {$this->stats['niveau_3']}");

        if ($this->stats['errors'] > 0) {
            $this->command->error("\n❌ Erreurs: {$this->stats['errors']}");

            if (!empty($this->errors)) {
                $this->command->error("\n⚠ Premières erreurs:");
                foreach (array_slice($this->errors, 0, 10) as $error) {
                    $this->command->error("   - Cursus {$error['cursus_id']} (Etudiant {$error['etudiant_id']}): {$error['error']}");
                }
            }
        } else {
            $this->command->info("\n✅ Aucune erreur");
        }
    }
}

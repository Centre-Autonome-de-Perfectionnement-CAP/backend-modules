<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\EntryDiploma;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\AcademicPath;
use Carbon\Carbon;

/**
 * Migration des candidats en attente (etudiant_en_attentes)
 * Ces candidats ont leurs pièces/documents
 */
class PendingStudentsMigrationSeeder extends Seeder
{
    private $sqlFile;
    private $stats = [
        'total' => 0,
        'success' => 0,
        'errors' => 0,
    ];
    private $errors = [];

    private $idMapping = [
        'cycles' => [],
        'departments' => [],
        'diplomas' => [],
        'academic_years' => [],
    ];

    public function __construct()
    {
        $this->sqlFile = database_path('../u374405408_progiciel_cap (2).sql');
    }

    public function run(): void
    {
        $this->command->info('🚀 Début de la migration des candidats en attente (avec pièces)...');

        if (!file_exists($this->sqlFile)) {
            $this->command->error('❌ Fichier SQL introuvable');
            return;
        }

        // Charger les mappings
        $this->loadIdMappings();

        // Extraire les candidats
        $candidates = $this->extractCandidatesFromSQL();

        if (empty($candidates)) {
            $this->command->error('❌ Aucun candidat trouvé');
            return;
        }

        $this->command->info('📊 ' . count($candidates) . ' candidats trouvés');

        $bar = $this->command->getOutput()->createProgressBar(count($candidates));
        $bar->start();

        foreach ($candidates as $candidate) {
            try {
                $this->migrateCandidate($candidate);
                $this->stats['success']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->errors[] = [
                    'nom' => $candidate['nom'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->displayStats();
    }

    private function loadIdMappings(): void
    {
        // Cycles
        $cycles = Cycle::all();
        foreach ($cycles as $cycle) {
            if ($cycle->name === 'Licence Professionnelle') $this->idMapping['cycles'][7] = $cycle->id;
            if ($cycle->name === 'Master Professionnel') $this->idMapping['cycles'][8] = $cycle->id;
            if ($cycle->name === 'Ingénierie') $this->idMapping['cycles'][9] = $cycle->id;
        }

        // Diplômes
        $diplomas = EntryDiploma::all();
        foreach ($diplomas as $diploma) {
            $this->idMapping['diplomas'][$diploma->id] = $diploma->id;
        }

        // Années académiques
        $years = AcademicYear::all();
        foreach ($years as $year) {
            $startYear = (int) substr($year->academic_year, 0, 4);
            $this->idMapping['academic_years'][$startYear] = $year->id;
        }

        // Départements - Mapping manuel ancien_id => nouveau_id
        $departmentMapping = [
            27 => 1,   // GC Licence
            28 => 2,   // GE Licence
            29 => 3,   // GT Licence
            30 => 4,   // PA
            31 => 5,   // PV
            32 => 6,   // Gen
            33 => 7,   // HCQ
            34 => 8,   // BSS
            35 => 9,   // ABM
            36 => 10,  // NDTA
            37 => 11,  // GR
            38 => 12,  // MI
            39 => 13,  // MA
            40 => 14,  // HYD
            41 => 15,  // FM
            42 => 16,  // FC
            43 => 17,  // PVPR Master
            44 => 18,  // GC Ingénieur
            45 => 19,  // GT Ingénieur
            46 => 20,  // GE Ingénieur
            47 => 21,  // GME Licence
            48 => 22,  // GMP Licence
        ];

        $this->idMapping['departments'] = $departmentMapping;
    }

    private function extractCandidatesFromSQL(): array
    {
        $this->command->info('🔍 Extraction depuis le fichier SQL...');

        $sqlContent = file_get_contents($this->sqlFile);

        // Pattern pour trouver tous les INSERT de etudiant_en_attentes
        $pattern = '/INSERT INTO `etudiant_en_attentes`.*?VALUES\s*(.*?);/s';

        preg_match_all($pattern, $sqlContent, $allMatches, PREG_SET_ORDER);

        if (empty($allMatches)) {
            return [];
        }

        $candidates = [];

        foreach ($allMatches as $match) {
            $insertData = $match[1];
            $extractedRows = $this->extractRows($insertData);

            foreach ($extractedRows as $row) {
                $values = $this->parseRow($row);
                $candidates[] = $values;
            }
        }

        return $candidates;
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
                        $rows[] = $currentRow;
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

    private function parseRow(string $row): array
    {
        $values = str_getcsv($row, ',', "'");
        $values = array_pad($values, 25, '');

        return [
            'id' => !empty($values[0]) ? (int) $values[0] : 0,
            'matricule' => !empty($values[1]) && $values[1] !== 'NULL' ? $values[1] : null,
            'nom' => $values[2] ?? 'NOM-INCONNU',
            'prenoms' => $values[3] ?? 'PRENOM-INCONNU',
            'email' => !empty($values[4]) && $values[4] !== 'NULL' ? $values[4] : null,
            'used' => (int) ($values[5] ?? 0),
            'date_naissance' => !empty($values[6]) && $values[6] !== 'NULL' ? $values[6] : null,
            'lieu_de_naissance' => $values[7] ?? '',
            'pays_de_naissance' => $values[8] ?? 'Bénin',
            'genre' => $values[9] ?? 'masculin',
            'password' => !empty($values[10]) && $values[10] !== 'NULL' ? $values[10] : null,
            'role' => !empty($values[11]) && $values[11] !== 'NULL' ? $values[11] : null,
            'photo' => !empty($values[12]) && $values[12] !== 'NULL' ? $values[12] : null,
            'cursus_valide' => (int) ($values[13] ?? 0),
            'ne_vers' => (int) ($values[14] ?? 0),
            'filiere_id' => !empty($values[15]) ? (int) $values[15] : 27,
            'diplome_entree_id' => !empty($values[16]) ? (int) $values[16] : 1,
            'annee_entree' => !empty($values[17]) ? (int) $values[17] : date('Y'),
            'contact' => $values[18] ?? '[""]',
            'type' => $values[19] ?? 'license',
            'avis_cuca' => (int) ($values[20] ?? 0),
            'avis_cuo' => (int) ($values[21] ?? 0),
            'pieces' => !empty($values[22]) && $values[22] !== 'NULL' ? $values[22] : null, // LE CHAMP IMPORTANT
            'motif' => !empty($values[23]) && $values[23] !== 'NULL' ? $values[23] : null,
        ];
    }

    private function migrateCandidate(array $candidate): void
    {
        $this->stats['total']++;

        DB::beginTransaction();

        try {
            // 1. Créer ou trouver PersonalInformation
            $personalInfo = $this->createOrFindPersonalInformation($candidate);

            // 2. Créer PendingStudent avec les pièces
            $pendingStudent = $this->createPendingStudentWithDocuments($candidate, $personalInfo);

            // 3. Chercher si un Student existe déjà (via pending_students avec même personal_information)
            // Ne JAMAIS créer de student depuis etudiant_en_attentes
            $existingPendingStudent = PendingStudent::where('personal_information_id', $personalInfo->id)
                ->where('tracking_code', 'LIKE', 'MIG-%')
                ->first();

            if ($existingPendingStudent) {
                // Trouver le student via le lien student_pending_student
                $existingLink = StudentPendingStudent::where('pending_student_id', $existingPendingStudent->id)->first();

                if ($existingLink) {
                    // Créer le lien entre le student existant et ce nouveau pending_student
                    StudentPendingStudent::create([
                        'student_id' => $existingLink->student_id,
                        'pending_student_id' => $pendingStudent->id,
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createOrFindPersonalInformation(array $candidate): PersonalInformation
    {
        // Extraire les contacts
        $contacts = $this->extractContacts($candidate['contact'] ?? '');
        if (empty($contacts)) {
            $contacts = ['+229 ' . rand(90000000, 99999999)];
        }

        $lastName = !empty($candidate['nom']) ? $candidate['nom'] : 'NOM-INCONNU';
        $firstNames = !empty($candidate['prenoms']) ? $candidate['prenoms'] : 'PRENOM-INCONNU';
        $gender = (strtolower($candidate['genre']) === 'feminin' || strtolower($candidate['genre']) === 'f') ? 'F' : 'M';

        $birthDate = null;
        if (!empty($candidate['date_naissance']) && $candidate['date_naissance'] !== 'NULL') {
            try {
                $birthDate = Carbon::parse($candidate['date_naissance']);
            } catch (\Exception $e) {
                // Date invalide
            }
        }

        // Chercher d'abord si existe déjà
        $existing = PersonalInformation::where('last_name', $lastName)
            ->where('first_names', $firstNames)
            ->where('birth_date', $birthDate)
            ->first();

        if ($existing) {
            return $existing;
        }

        return PersonalInformation::create([
            'last_name' => $lastName,
            'first_names' => $firstNames,
            'email' => !empty($candidate['email']) && $candidate['email'] !== 'NULL' ? $candidate['email'] : null,
            'birth_date' => $birthDate,
            'birth_place' => $candidate['lieu_de_naissance'] ?? '',
            'birth_country' => $candidate['pays_de_naissance'] ?? 'Bénin',
            'gender' => $gender,
            'contacts' => $contacts,
            'nationality' => ($candidate['pays_de_naissance'] ?? 'Bénin') === 'Bénin' ? 'Béninoise' : $candidate['pays_de_naissance'],
            'photo' => !empty($candidate['photo']) && $candidate['photo'] !== 'NULL' ? $candidate['photo'] : null,
        ]);
    }

    private function createPendingStudentWithDocuments(array $candidate, PersonalInformation $personalInfo): PendingStudent
    {
        // Département
        $departmentId = $this->idMapping['departments'][$candidate['filiere_id']] ?? null;
        if (!$departmentId) {
            $departmentId = Department::first()->id;
        }

        // Année académique
        $anneeEntree = $candidate['annee_entree'] ?? date('Y');
        $academicYearId = $this->idMapping['academic_years'][$anneeEntree] ?? null;

        if (!$academicYearId) {
            $academicYearString = $anneeEntree . '-' . ($anneeEntree + 1);
            $academicYear = AcademicYear::firstOrCreate(
                ['academic_year' => $academicYearString],
                [
                    'libelle' => 'Année Académique ' . $academicYearString,
                    'year_start' => Carbon::create($anneeEntree, 10, 1),
                    'year_end' => Carbon::create($anneeEntree + 1, 6, 30),
                    'submission_start' => Carbon::create($anneeEntree, 8, 1),
                    'submission_end' => Carbon::create($anneeEntree, 9, 30),
                    'reclamation_start' => Carbon::create($anneeEntree + 1, 7, 1),
                    'reclamation_end' => Carbon::create($anneeEntree + 1, 7, 31),
                    'is_current' => false,
                ]
            );
            $academicYearId = $academicYear->id;
        }

        // Diplôme d'entrée
        $entryDiplomaId = $this->idMapping['diplomas'][$candidate['diplome_entree_id']] ?? null;
        if (!$entryDiplomaId) {
            $entryDiplomaId = EntryDiploma::first()?->id;
        }

        // Parser les pièces/documents JSON
        $documents = null;
        if (!empty($candidate['pieces']) && $candidate['pieces'] !== 'NULL') {
            // Nettoyer les échappements du JSON
            $cleanedPieces = stripslashes($candidate['pieces']);
            $decodedDocs = json_decode($cleanedPieces, true);

            if (is_array($decodedDocs) && !empty($decodedDocs)) {
                $documents = $decodedDocs;
            } else {
                // Si le décodage échoue, essayer sans stripslashes
                $decodedDocs = json_decode($candidate['pieces'], true);
                if (is_array($decodedDocs) && !empty($decodedDocs)) {
                    $documents = $decodedDocs;
                }
            }
        }

        // Déterminer le status
        $status = 'pending';
        if ($candidate['avis_cuca'] == 1 && $candidate['avis_cuo'] == 1) {
            $status = 'approved';
        } elseif ($candidate['used'] == 1) {
            $status = 'approved';
        }

        $cucaOpinion = $candidate['avis_cuca'] == 1 ? 'favorable' : ($candidate['avis_cuca'] == -1 ? 'defavorable' : 'pending');
        $cuoOpinion = $candidate['avis_cuo'] == 1 ? 'favorable' : ($candidate['avis_cuo'] == -1 ? 'defavorable' : 'pending');

        // Générer un tracking_code unique
        $matricule = trim($candidate['matricule'] ?? '');
        $trackingCode = 'PEND-';

        if (!empty($matricule) && strtoupper($matricule) !== 'NULL' && $matricule !== 'null') {
            $trackingCode .= $matricule;
        } else {
            // Utiliser l'ID + hash du nom pour garantir l'unicité
            $trackingCode .= $candidate['id'] . '-' . substr(md5($candidate['nom'] . $candidate['prenoms']), 0, 6);
        }

        return PendingStudent::create([
            'tracking_code' => $trackingCode,
            'personal_information_id' => $personalInfo->id,
            'academic_year_id' => $academicYearId,
            'department_id' => $departmentId,
            'entry_diploma_id' => $entryDiplomaId,
            'level' => '1', // Simplifié: niveau 1',
            'status' => $status,
            'documents' => $documents, // ✅ LES PIÈCES SONT ICI
            'photo' => $candidate['photo'],
            'cuca_opinion' => $cucaOpinion,
            'cuo_opinion' => $cuoOpinion,
            'cuca_comment' => $candidate['motif'],
            'rejection_reason' => $status === 'rejected' ? $candidate['motif'] : null,
            'sponsorise' => 'Non',
        ]);
    }

    private function extractContacts(string $contactsData): array
    {
        $contacts = [];
        $contactsData = trim($contactsData);

        if (empty($contactsData) || $contactsData === 'NULL' || $contactsData === '[""]' || $contactsData === '[]') {
            return [];
        }

        $decoded = json_decode($contactsData, true);
        if (is_array($decoded)) {
            $contacts = array_filter($decoded, function($contact) {
                return !empty($contact) && $contact !== '' && $contact !== 'NULL';
            });
            $contacts = array_values($contacts);
        }

        if (empty($contacts)) {
            preg_match_all('/(\+229\s*\d{8,11}|00229\s*\d{8,11}|229\s*\d{8,11}|\d{8,11})/', $contactsData, $matches);
            if (!empty($matches[0])) {
                $contacts = array_map('trim', $matches[0]);
            }
        }

        if (empty($contacts)) {
            preg_match_all('/\d{8,}/', $contactsData, $matches);
            if (!empty($matches[0])) {
                $contacts = $matches[0];
            }
        }

        return array_values(array_unique($contacts));
    }

    private function displayStats(): void
    {
        $this->command->info('📊 Statistiques:');
        $this->command->info('   ✅ Total traité: ' . $this->stats['total']);
        $this->command->info('   ✅ Succès: ' . $this->stats['success']);
        $this->command->info('   ❌ Erreurs: ' . $this->stats['errors']);

        if (!empty($this->errors)) {
            $this->command->error("\n⚠ Erreurs détaillées:");
            foreach (array_slice($this->errors, 0, 10) as $error) {
                $this->command->error('   - ' . $error['nom'] . ': ' . $error['error']);
            }
        }
    }
}

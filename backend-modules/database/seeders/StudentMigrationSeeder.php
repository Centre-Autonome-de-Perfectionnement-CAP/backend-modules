<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\EntryDiploma;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\Student;
use Carbon\Carbon;

class StudentMigrationSeeder extends Seeder
{
    private $sqlFile;
    private $stats = [
        'total' => 0,
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];
    private $errors = [];
    
    // Tables de mapping old_id => new_id
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

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Début de la migration des étudiants...');
        
        // Vérifier que le fichier SQL existe
        if (!file_exists($this->sqlFile)) {
            $this->command->error('❌ Fichier SQL introuvable: ' . $this->sqlFile);
            return;
        }

        // Charger les mappings d'IDs
        $this->loadIdMappings();

        // Extraire et migrer les étudiants
        $students = $this->extractStudentsFromSQL();
        
        if (empty($students)) {
            $this->command->error('❌ Aucun étudiant trouvé dans le fichier SQL');
            return;
        }

        $this->command->info('📊 ' . count($students) . ' étudiants trouvés dans l\'ancienne base');
        
        // Barre de progression
        $bar = $this->command->getOutput()->createProgressBar(count($students));
        $bar->start();

        foreach ($students as $oldStudent) {
            try {
                $this->migrateStudent($oldStudent);
                $this->stats['success']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->errors[] = [
                    'matricule' => $oldStudent['matricule'] ?? 'N/A',
                    'nom' => $oldStudent['nom'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
                $this->command->error("\n⚠ Erreur pour " . ($oldStudent['matricule'] ?? 'N/A') . ": " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);
        
        // Afficher les statistiques
        $this->displayStats();
    }

    /**
     * Charger les mappings d'IDs depuis la base de données
     */
    private function loadIdMappings(): void
    {
        $this->command->info('📋 Chargement des mappings...');
        
        // Mapping cycles
        $oldCycles = [
            7 => 'Licence Professionnelle',
            8 => 'Master Professionnel',
            9 => 'Ingénierie',
        ];
        
        foreach ($oldCycles as $oldId => $name) {
            $cycle = Cycle::where('name', $name)->first();
            if ($cycle) {
                $this->idMapping['cycles'][$oldId] = $cycle->id;
            }
        }

        // Mapping diplômes: ancienne base → EntryDiplomaSeeder
        // Ancien ID => Nom EXACT du diplôme dans EntryDiplomaSeeder
        $oldDiplomasMapping = [
            1 => 'Baccalauréat Scientifique',              // BAC Scientifique
            2 => 'DUT (Diplôme Universitaire de Technologie)',  // DTI
            3 => 'DUT (Diplôme Universitaire de Technologie)',  // DIT
            4 => 'DUT (Diplôme Universitaire de Technologie)',  // DEAT
            5 => 'DUT (Diplôme Universitaire de Technologie)',  // DUT
            6 => 'BTS (Brevet de Technicien Supérieur)',   // BTS
            7 => 'Licence Professionnelle',                // Licence Pro
        ];
        
        foreach ($oldDiplomasMapping as $oldId => $exactName) {
            $diploma = EntryDiploma::where('name', $exactName)->first();
            
            if ($diploma) {
                $this->idMapping['diplomas'][$oldId] = $diploma->id;
                $this->command->info("  ✓ Diplôme ancien ID {$oldId} → {$diploma->name}");
            } else {
                // Fallback: utiliser "Baccalauréat Scientifique" par défaut
                $fallback = EntryDiploma::where('name', 'Baccalauréat Scientifique')->first();
                $this->idMapping['diplomas'][$oldId] = $fallback?->id;
                $this->command->warn("  ⚠ Diplôme '{$exactName}' non trouvé, fallback vers '{$fallback?->name}'");
            }
        }

        // Mapping départements
        $departments = Department::all();
        foreach ($departments as $dept) {
            // On va mapper par nom et cycle pour retrouver les anciens IDs
            $this->idMapping['departments'][$dept->name . '_' . $dept->cycle_id] = $dept->id;
        }

        // Mapping années académiques
        $academicYears = AcademicYear::all();
        foreach ($academicYears as $year) {
            // Format: 2024-2025 => extraire 2024
            $startYear = (int) substr($year->academic_year, 0, 4);
            $this->idMapping['academic_years'][$startYear] = $year->id;
        }

        $this->command->info('   ✓ Mappings chargés');
    }

    /**
     * Extraire les étudiants du fichier SQL ET FUSIONNER LES DOUBLONS
     */
    private function extractStudentsFromSQL(): array
    {
        $this->command->info('🔍 Extraction des données depuis le fichier SQL...');
        
        $sqlContent = file_get_contents($this->sqlFile);
        
        // Trouver TOUTES les sections INSERT pour la table etudiants (peut y avoir plusieurs INSERT séparés)
        $pattern = '/INSERT INTO `etudiants`.*?VALUES\s*(.*?);/s';
        
        preg_match_all($pattern, $sqlContent, $allMatches, PREG_SET_ORDER);
        
        if (empty($allMatches)) {
            return [];
        }

        $allStudents = [];
        
        // Parcourir tous les INSERT trouvés
        foreach ($allMatches as $match) {
            $insertData = $match[1];
            
            // Parser les valeurs en tenant compte des parenthèses et guillemets
            $extractedRows = $this->extractRows($insertData);
            
            foreach ($extractedRows as $row) {
                // Parser chaque ligne - ne rejette jamais une ligne
                $values = $this->parseRow($row);
                $allStudents[] = $values;
            }
        }

        $totalLines = count($allStudents);
        $this->command->info("   ✓ {$totalLines} lignes extraites");
        
        // 🔥 NOUVEAU: Pré-fusionner les doublons AVANT la migration
        $mergedStudents = $this->preMergeStudentDuplicates($allStudents);
        
        return $mergedStudents;
    }
    
    /**
     * Pré-fusionner les doublons d'étudiants de l'ancienne base
     * (ex: même matricule avec email=NULL et email renseigné)
     */
    private function preMergeStudentDuplicates(array $students): array
    {
        $this->command->info('🔄 Pré-fusion des doublons dans l\'ancienne base...');
        
        // Grouper par identité unique
        $groups = [];
        
        foreach ($students as $student) {
            // Clé d'identité: matricule OU (nom + prénom + date + lieu)
            $key = $this->getStudentIdentityKey($student);
            
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            
            $groups[$key][] = $student;
        }
        
        // Fusionner chaque groupe
        $mergedStudents = [];
        $duplicatesFound = 0;
        
        foreach ($groups as $key => $group) {
            if (count($group) > 1) {
                $duplicatesFound++;
                
                // Debug spécifique pour SINSIN Janvier
                $isDebugTarget = ($group[0]['nom'] === 'SINSIN' && $group[0]['prenoms'] === 'Janvier');
                
                if ($isDebugTarget) {
                    $this->command->warn("  🔬 DEBUG: SINSIN Janvier - " . count($group) . " lignes trouvées");
                    foreach ($group as $idx => $rec) {
                        $this->command->info("     Ligne {$idx}: email=" . var_export($rec['email'] ?? null, true) . ", photo=" . var_export($rec['photo'] ?? null, true));
                    }
                }
                
                $this->command->info("  ⚠️  Doublon pré-fusion: {$group[0]['nom']} {$group[0]['prenoms']} (" . count($group) . " lignes)");
            }
            
            // Fusionner toutes les lignes du groupe en UNE SEULE
            $merged = $this->mergeStudentRecords($group);
            
            // Debug après fusion pour SINSIN Janvier
            if (isset($isDebugTarget) && $isDebugTarget) {
                $this->command->info("     APRÈS FUSION: email=" . var_export($merged['email'] ?? null, true) . ", photo=" . var_export($merged['photo'] ?? null, true));
            }
            
            $mergedStudents[] = $merged;
        }
        
        $uniqueCount = count($mergedStudents);
        
        $this->command->info("   ✓ {$duplicatesFound} doublons pré-fusionnés");
        $this->command->info("   ✓ {$uniqueCount} étudiants uniques à migrer");
        
        return $mergedStudents;
    }
    
    /**
     * Générer une clé d'identité unique pour un étudiant
     */
    private function getStudentIdentityKey(array $student): string
    {
        // Priorité 1: Matricule (si valide)
        $matricule = trim($student['matricule'] ?? '');
        if (!empty($matricule) && strtoupper($matricule) !== 'NULL' && $matricule !== 'null') {
            return 'MAT_' . $matricule;
        }
        
        // Priorité 2: Nom + Prénom + Date + Lieu
        $nom = mb_strtolower(trim($student['nom'] ?? ''));
        $prenoms = mb_strtolower(trim($student['prenoms'] ?? ''));
        $date = trim($student['date_naissance'] ?? '');
        $lieu = mb_strtolower(trim($student['lieu_de_naissance'] ?? ''));
        
        return "IDENT_{$nom}|{$prenoms}|{$date}|{$lieu}";
    }
    
    /**
     * Fusionner plusieurs enregistrements d'étudiants en un seul
     * En gardant les valeurs NON-NULL
     */
    private function mergeStudentRecords(array $records): array
    {
        if (count($records) === 1) {
            return $records[0];
        }
        
        // Commencer avec le premier enregistrement
        $merged = $records[0];
        
        // Champs à fusionner (garder la première valeur NON-NULL trouvée)
        $fieldsToMerge = [
            'email', 'photo', 'date_naissance', 'lieu_de_naissance', 
            'pays_de_naissance', 'contacts', 'password', 'role'
        ];
        
        // Parcourir les autres enregistrements
        for ($i = 1; $i < count($records); $i++) {
            $record = $records[$i];
            
            foreach ($fieldsToMerge as $field) {
                // Si le champ est NULL ou vide dans merged, prendre la valeur de record
                $mergedValue = trim($merged[$field] ?? '');
                $recordValue = trim($record[$field] ?? '');
                
                if ((empty($mergedValue) || $mergedValue === 'NULL') 
                    && !empty($recordValue) && $recordValue !== 'NULL') {
                    $merged[$field] = $record[$field];
                }
            }
            
            // Pour les contacts, fusionner les arrays
            if (!empty($record['contacts']) && $record['contacts'] !== '[""]' && $record['contacts'] !== 'NULL') {
                $mergedContacts = json_decode($merged['contacts'] ?? '[]', true) ?: [];
                $recordContacts = json_decode($record['contacts'] ?? '[]', true) ?: [];
                
                $allContacts = array_unique(array_merge($mergedContacts, $recordContacts));
                $allContacts = array_filter($allContacts, function($c) {
                    return !empty($c) && $c !== '' && $c !== 'NULL';
                });
                
                if (!empty($allContacts)) {
                    $merged['contacts'] = json_encode(array_values($allContacts));
                }
            }
        }
        
        return $merged;
    }

    /**
     * Extraire les lignes de valeurs en tenant compte des parenthèses imbriquées
     */
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
            
            // Gérer les guillemets (simples et doubles)
            if (($char === "'" || $char === '"') && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }
            
            // Si on n'est pas dans une chaîne, gérer les parenthèses
            if (!$inString) {
                if ($char === '(') {
                    $depth++;
                    if ($depth === 1) {
                        // Début d'une nouvelle ligne
                        $currentRow = '';
                        continue;
                    }
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        // Fin de la ligne
                        $rows[] = $currentRow;
                        $currentRow = '';
                        continue;
                    }
                }
            }
            
            // Ajouter le caractère à la ligne courante
            if ($depth > 0) {
                $currentRow .= $char;
            }
        }
        
        return $rows;
    }

    /**
     * Parser une ligne de données
     */
    private function parseRow(string $row): ?array
    {
        // Utiliser str_getcsv pour parser les valeurs séparées par des virgules
        // en tenant compte des guillemets et des échappements
        $values = str_getcsv($row, ',', "'");
        
        // Ne plus rejeter les lignes courtes, juste remplir avec des valeurs par défaut
        // Assurer qu'on a au moins 16 valeurs
        $values = array_pad($values, 16, '');

        return [
            'id' => !empty($values[0]) ? (int) $values[0] : 0,
            'nom' => $values[1] ?? 'NOM-INCONNU',
            'prenoms' => $values[2] ?? 'PRENOM-INCONNU',
            'contacts' => $values[3] ?? '[""]',
            'matricule' => !empty($values[4]) && $values[4] !== 'NULL' ? $values[4] : null,
            'date_naissance' => !empty($values[5]) && $values[5] !== 'NULL' ? $values[5] : null,
            'lieu_de_naissance' => $values[6] ?? 'Lieu inconnu',
            'pays_de_naissance' => $values[7] ?? 'Bénin',
            'genre' => $values[8] ?? 'masculin',
            'email' => !empty($values[9]) && $values[9] !== 'NULL' ? $values[9] : null,
            'password' => !empty($values[10]) && $values[10] !== 'NULL' ? $values[10] : null,
            'role' => !empty($values[11]) && $values[11] !== 'NULL' ? $values[11] : null,
            'photo' => !empty($values[12]) && $values[12] !== 'NULL' ? $values[12] : null,
            'filiere_id' => !empty($values[13]) ? (int) $values[13] : 27, // Par défaut Génie Civil
            'diplome_entree_id' => !empty($values[14]) ? (int) $values[14] : 1,
            'annee_entree' => !empty($values[15]) ? (int) $values[15] : date('Y'),
            'documents' => !empty($values[20]) && $values[20] !== 'NULL' ? $values[20] : null, // Champ pieces/documents (position approximative)
        ];
    }

    /**
     * Migrer un étudiant
     */
    private function migrateStudent(array $oldStudent): void
    {
        $this->stats['total']++;

        // Nettoyer et générer un matricule si manquant
        $matricule = trim($oldStudent['matricule'] ?? '');
        if (empty($matricule) || strtoupper($matricule) === 'NULL' || $matricule === 'null') {
            // Générer un matricule unique basé sur le nom et un timestamp
            $matricule = 'GEN-' . substr(md5($oldStudent['nom'] . $oldStudent['prenoms'] . time() . rand()), 0, 8);
        }
        $oldStudent['matricule'] = $matricule;

        DB::beginTransaction();
        
        try {
            // 1. Créer PersonalInformation
            $personalInfo = $this->createPersonalInformation($oldStudent);
            
            // 2. Créer PendingStudent
            $pendingStudent = $this->createPendingStudent($oldStudent, $personalInfo);
            
            // 3. Créer Student
            $student = $this->createStudent($oldStudent);
            
            // 4. Créer StudentPendingStudent (pivot)
            $this->createStudentPendingStudentLink($student, $pendingStudent);
            
            // 5. Créer AcademicPath
            $this->createAcademicPath($student, $pendingStudent, $oldStudent);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Créer ou récupérer PersonalInformation (avec gestion des doublons)
     */
    private function createPersonalInformation(array $oldStudent): PersonalInformation
    {
        // Préparer les données
        $lastName = !empty($oldStudent['nom']) ? $oldStudent['nom'] : 'NOM-INCONNU';
        $firstNames = !empty($oldStudent['prenoms']) ? $oldStudent['prenoms'] : 'PRENOM-INCONNU';
        
        $birthDate = null;
        if (!empty($oldStudent['date_naissance']) && $oldStudent['date_naissance'] !== 'NULL') {
            try {
                $birthDate = Carbon::parse($oldStudent['date_naissance']);
            } catch (\Exception $e) {
                // Date invalide, on laisse null
            }
        }
        
        $birthPlace = $oldStudent['lieu_de_naissance'] ?? '';

        // 🔍 ÉTAPE 1: Rechercher les doublons STRICTEMENT avec les 4 paramètres
        // Nom + Prénom + Date de naissance + Lieu de naissance
        $existingPersonalInfos = collect();
        
        if ($birthDate && $birthPlace) {
            // Recherche stricte avec les 4 critères
            $existingPersonalInfos = PersonalInformation::query()
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($lastName)])
                ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($firstNames)])
                ->whereDate('birth_date', $birthDate)
                ->whereRaw('LOWER(birth_place) = ?', [mb_strtolower($birthPlace)])
                ->get();
        }
        
        // 📊 ÉTAPE 2: Si des doublons existent, choisir le meilleur et compléter les infos manquantes
        if ($existingPersonalInfos->count() > 0) {
            $this->command->warn("  ⚠ Doublon détecté pour {$lastName} {$firstNames} ({$existingPersonalInfos->count()} trouvé(s))");
            
            // Choisir celui avec le plus de données/relations
            $best = $this->chooseBestPersonalInformation($existingPersonalInfos, $oldStudent);
            
            // ✨ Compléter UNIQUEMENT email et photo si NULL
            $updated = false;
            $updatedFields = [];
            
            // Vérifier et compléter l'email
            if (empty($best->email) && !empty($oldStudent['email']) && $oldStudent['email'] !== 'NULL') {
                $best->email = $oldStudent['email'];
                $updated = true;
                $updatedFields[] = 'email';
            }
            
            // Vérifier et compléter la photo
            if (empty($best->photo) && !empty($oldStudent['photo']) && $oldStudent['photo'] !== 'NULL') {
                $best->photo = $oldStudent['photo'];
                $updated = true;
                $updatedFields[] = 'photo';
            }
            
            // Sauvegarder les modifications si nécessaire
            if ($updated) {
                $best->save();
                $this->command->info("  🔄 Mis à jour: " . implode(', ', $updatedFields));
            }
            
            $this->command->info("  ✅ Réutilisation de PersonalInformation ID: {$best->id}");
            
            return $best;
        }

        // 📝 ÉTAPE 3: Aucun doublon, créer normalement
        $contacts = $this->extractContacts($oldStudent['contacts'] ?? '');
        if (empty($contacts)) {
            $contacts = ['+229 ' . rand(90000000, 99999999)];
        }

        $gender = 'M';
        if (!empty($oldStudent['genre'])) {
            $gender = (strtolower($oldStudent['genre']) === 'feminin' || strtolower($oldStudent['genre']) === 'féminin' || strtolower($oldStudent['genre']) === 'f') ? 'F' : 'M';
        }

        return PersonalInformation::create([
            'last_name' => $lastName,
            'first_names' => $firstNames,
            'email' => !empty($oldStudent['email']) && $oldStudent['email'] !== 'NULL' ? $oldStudent['email'] : null,
            'birth_date' => $birthDate,
            'birth_place' => $birthPlace,
            'birth_country' => $oldStudent['pays_de_naissance'] ?? 'Bénin',
            'gender' => $gender,
            'contacts' => $contacts,
            'nationality' => ($oldStudent['pays_de_naissance'] ?? 'Bénin') === 'Bénin' ? 'Béninoise' : ($oldStudent['pays_de_naissance'] ?? 'Béninoise'),
            'photo' => !empty($oldStudent['photo']) && $oldStudent['photo'] !== 'NULL' ? $oldStudent['photo'] : null,
        ]);
    }

    /**
     * Choisir le meilleur PersonalInformation parmi les doublons
     */
    private function chooseBestPersonalInformation($personalInfos, array $newData)
    {
        $best = null;
        $bestScore = -1;

        foreach ($personalInfos as $pi) {
            // Calculer un score basé sur:
            // - Nombre de pending_students liés
            // - Nombre d'academic_paths
            // - Complétude des données (email, photo, etc.)
            
            $score = 0;
            
            // Relations
            $score += $pi->pendingStudents()->count() * 10;
            $score += $pi->pendingStudents()->withCount('academicPaths')->get()->sum('academic_paths_count') * 5;
            
            // Complétude des données
            $score += filled($pi->email) ? 2 : 0;
            $score += filled($pi->photo) ? 1 : 0;
            $score += count($pi->contacts ?? []) > 0 ? 1 : 0;
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $pi;
            }
        }

        return $best ?? $personalInfos->first();
    }

    /**
     * Extraire les contacts avec plusieurs stratégies de fallback
     */
    private function extractContacts(string $contactsData): array
    {
        $contacts = [];

        // Nettoyer la donnée
        $contactsData = trim($contactsData);

        if (empty($contactsData) || $contactsData === 'NULL' || $contactsData === '[""]' || $contactsData === '[]') {
            return [];
        }

        // Stratégie 1: JSON decode
        $decoded = json_decode($contactsData, true);
        if (is_array($decoded)) {
            $contacts = array_filter($decoded, function($contact) {
                return !empty($contact) && $contact !== '' && $contact !== 'NULL';
            });
            $contacts = array_values($contacts); // Ré-indexer
        }

        // Stratégie 2: Si le JSON decode a échoué, essayer de parser manuellement
        if (empty($contacts)) {
            // Chercher des patterns de numéros de téléphone: +229, 00229, 229, ou 8-9 chiffres
            preg_match_all('/(\+229\s*\d{8,11}|00229\s*\d{8,11}|229\s*\d{8,11}|\d{8,11})/', $contactsData, $matches);
            if (!empty($matches[0])) {
                $contacts = array_map('trim', $matches[0]);
            }
        }

        // Stratégie 3: Si toujours rien, chercher n'importe quel groupe de chiffres
        if (empty($contacts)) {
            preg_match_all('/\d{8,}/', $contactsData, $matches);
            if (!empty($matches[0])) {
                $contacts = $matches[0];
            }
        }

        return array_values(array_unique($contacts));
    }

    /**
     * Créer PendingStudent
     */
    private function createPendingStudent(array $oldStudent, PersonalInformation $personalInfo): PendingStudent
    {
        // Trouver le département avec fallback
        $department = $this->findDepartment($oldStudent['filiere_id'] ?? 0);
        if (!$department) {
            // Fallback: utiliser le premier département de Licence
            $department = Department::where('cycle_id', $this->idMapping['cycles'][7] ?? 1)->first();
            if (!$department) {
                // Fallback ultime: prendre n'importe quel département
                $department = Department::first();
            }
        }

        // Trouver l'année académique avec fallback
        $anneeEntree = $oldStudent['annee_entree'] ?? date('Y');
        $academicYearId = $this->idMapping['academic_years'][$anneeEntree] ?? null;
        
        if (!$academicYearId) {
            // Créer l'année si elle n'existe pas
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
            $this->idMapping['academic_years'][$anneeEntree] = $academicYearId;
        }

        // Trouver le diplôme d'entrée avec fallback
        $entryDiplomaId = $this->idMapping['diplomas'][$oldStudent['diplome_entree_id'] ?? 1] ?? null;
        if (!$entryDiplomaId) {
            $entryDiplomaId = $this->idMapping['diplomas'][1] ?? EntryDiploma::first()?->id;
        }

        // Parser les documents/pieces si présents
        $documents = null;
        if (!empty($oldStudent['documents'])) {
            $decodedDocs = json_decode($oldStudent['documents'], true);
            if (is_array($decodedDocs)) {
                $documents = $decodedDocs;
            }
        }

        $trackingCode = 'MIG-' . $oldStudent['matricule'];
        
        // 🔍 Vérifier si un PendingStudent avec ce tracking_code existe déjà
        $existingPending = PendingStudent::where('tracking_code', $trackingCode)->first();
        
        if ($existingPending) {
            $this->command->info("  ✅ Réutilisation du PendingStudent avec tracking_code: {$trackingCode}");
            return $existingPending;
        }
        
        return PendingStudent::create([
            'tracking_code' => $trackingCode,
            'personal_information_id' => $personalInfo->id,
            'academic_year_id' => $academicYearId,
            'department_id' => $department->id,
            'entry_diploma_id' => $entryDiplomaId,
            'level' => '1', // Simplifié: niveau 1
            'status' => 'approved', // Déjà validé puisque dans l'ancienne DB
            'cuca_opinion' => 'favorable',
            'cuo_opinion' => 'favorable',
            'sponsorise' => 'Non',
            'documents' => $documents, // Documents/pieces de l'étudiant
        ]);
    }

    /**
     * Créer ou récupérer Student (avec gestion des doublons)
     */
    private function createStudent(array $oldStudent): Student
    {
        $matricule = $oldStudent['matricule'];
        
        // 🔍 Vérifier si un Student avec ce matricule existe déjà
        $existingStudent = Student::where('student_id_number', $matricule)->first();
        
        if ($existingStudent) {
            $this->command->info("  ✅ Réutilisation du Student avec matricule: {$matricule}");
            return $existingStudent;
        }
        
        // 📝 Aucun Student avec ce matricule, créer normalement
        return Student::create([
            'student_id_number' => $matricule,
            'password' => $oldStudent['password'] ?? Hash::make($matricule), // Utiliser le matricule comme mot de passe par défaut
        ]);
    }

    /**
     * Créer le lien StudentPendingStudent (avec vérification de doublon)
     */
    private function createStudentPendingStudentLink(Student $student, PendingStudent $pendingStudent): void
    {
        // 🔍 Vérifier si le lien existe déjà
        $exists = StudentPendingStudent::where('student_id', $student->id)
            ->where('pending_student_id', $pendingStudent->id)
            ->exists();
        
        if ($exists) {
            $this->command->info("  ⏭ Lien StudentPendingStudent déjà existant");
            return;
        }
        
        // 📝 Créer le lien
        StudentPendingStudent::create([
            'student_id' => $student->id,
            'pending_student_id' => $pendingStudent->id,
        ]);
    }

    /**
     * Créer Academic Path par défaut
     */
    private function createAcademicPath(Student $student, PendingStudent $pendingStudent, array $oldStudent): void
    {
        // Récupérer le lien student_pending_student
        $link = StudentPendingStudent::where('student_id', $student->id)
            ->where('pending_student_id', $pendingStudent->id)
            ->first();

        if (!$link) {
            $this->command->warn('⚠ Lien pivot manquant');
            return;
        }

        // Créer un academic_path par défaut (sera remplacé par le CursusesSeeder)
        AcademicPath::create([
            'student_pending_student_id' => $link->id,
            'academic_year_id' => $pendingStudent->academic_year_id,
            'study_level' => '1',
            'year_decision' => null,
            'financial_status' => 'Non exonéré',
            'cohort' => (string) ($oldStudent['annee_entree'] ?? date('Y')),
        ]);
    }

    /**
     * Trouver le département correspondant
     */
    private function findDepartment(int $oldFiliereId): ?Department
    {
        // Mapping manuel des filières
        $filiereMapping = [
            27 => ['nom' => 'Génie Civil', 'cycle' => 7],
            28 => ['nom' => 'Génie Electrique', 'cycle' => 7],
            29 => ['nom' => 'Géomètre Topographe', 'cycle' => 7],
            30 => ['nom' => 'Production Animale', 'cycle' => 7],
            31 => ['nom' => 'Production Végétale', 'cycle' => 7],
            32 => ['nom' => 'Génie de l\'Environnement', 'cycle' => 7],
            33 => ['nom' => 'Hygiène et Contrôle de Qualité', 'cycle' => 7],
            34 => ['nom' => 'Biohygiène et Sécurité Sanitaire', 'cycle' => 7],
            35 => ['nom' => 'Analyses Biomédicales', 'cycle' => 7],
            36 => ['nom' => 'Nutrition, Diététique et Technologie Alimentaire', 'cycle' => 7],
            37 => ['nom' => 'Génie Rural', 'cycle' => 7],
            38 => ['nom' => 'Maintenance Industrielle', 'cycle' => 7],
            39 => ['nom' => 'Mécanique Automobile', 'cycle' => 7],
            40 => ['nom' => 'Hydraulique', 'cycle' => 7],
            41 => ['nom' => 'Fabrication Mécanique', 'cycle' => 7],
            42 => ['nom' => 'Froid et Climatisation', 'cycle' => 7],
            43 => ['nom' => 'Production Végétale et Post-Récolte', 'cycle' => 8],
            44 => ['nom' => 'Génie Civil', 'cycle' => 9],
            45 => ['nom' => 'Géomètre Topographe', 'cycle' => 9],
            46 => ['nom' => 'Génie Electrique', 'cycle' => 9],
            47 => ['nom' => 'Génie Mécanique et Energétique', 'cycle' => 7],
            48 => ['nom' => 'Génie Mécanique et Productique', 'cycle' => 7],
        ];

        if (!isset($filiereMapping[$oldFiliereId])) {
            return null;
        }

        $filiere = $filiereMapping[$oldFiliereId];
        $newCycleId = $this->idMapping['cycles'][$filiere['cycle']] ?? null;

        if (!$newCycleId) {
            return null;
        }

        return Department::where('name', $filiere['nom'])
            ->where('cycle_id', $newCycleId)
            ->where('abbreviation', 'NOT LIKE', 'P-%')  // Exclure les départements Prépa
            ->orderBy('id', 'desc')  // Prendre le plus récent (ID le plus grand = Ingénieur)
            ->first();
    }

    /**
     * Afficher les statistiques
     */
    private function displayStats(): void
    {
        $this->command->info('📊 Statistiques de migration:');
        $this->command->info('   ✅ Total traité: ' . $this->stats['total']);
        $this->command->info('   ✅ Succès: ' . $this->stats['success']);
        $this->command->info('   ⏭  Ignorés (déjà existants): ' . $this->stats['skipped']);
        $this->command->info('   ❌ Erreurs: ' . $this->stats['errors']);

        if (!empty($this->errors)) {
            $this->command->error("\n⚠ Erreurs détaillées:");
            foreach (array_slice($this->errors, 0, 10) as $error) {
                $this->command->error('   - ' . $error['matricule'] . ' (' . $error['nom'] . '): ' . $error['error']);
            }
            
            if (count($this->errors) > 10) {
                $this->command->error('   ... et ' . (count($this->errors) - 10) . ' autres erreurs');
            }
        }
    }
}

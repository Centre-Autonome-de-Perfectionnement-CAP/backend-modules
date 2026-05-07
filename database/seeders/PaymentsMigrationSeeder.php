<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Finance\Models\Paiement;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Cycle;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Migration des paiements depuis l'ancienne base de données MySQL
 */
class PaymentsMigrationSeeder extends Seeder
{
    private $oldDb; // Connexion à l'ancienne BDD
    private $stats = [
        'total' => 0,
        'success' => 0,
        'skipped' => 0,
        'errors' => 0,
        'identity_not_found' => 0,
        'split_payments' => 0, // Paiements splittés Prépa/Spé
        'ingenieur_payments' => 0, // Paiements Ingénierie
        'normal_payments' => 0, // Paiements normaux
    ];
    private $errors = [];
    private $studentDetailedMapping = []; // ancien etudiant_id => ['nom', 'prenoms', 'date_naissance', 'lieu_naissance', 'matricule', 'filiere_id', 'annee_entree']
    private $personalInfoCache = []; // Cache pour éviter les recherches répétées

    public function __construct()
    {
        // Configuration de la connexion à l'ancienne BDD
        config([
            'database.connections.old_mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'u374405408_progiciel_cap',
                'username' => 'root',
                'password' => 'amede0430',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ]
        ]);

        $this->oldDb = DB::connection('old_mysql');
    }

    public function run(): void
    {
        $this->command->info('🚀 Migration des paiements depuis MySQL...');
        $this->command->newLine();

        try {
            // Tester la connexion
            $this->oldDb->select('SELECT 1');
            $this->command->info('✅ Connexion à l\'ancienne BDD établie');
        } catch (\Exception $e) {
            $this->command->error('❌ Impossible de se connecter à l\'ancienne BDD: ' . $e->getMessage());
            return;
        }

        $this->command->newLine();

        // Étape 1: Construire le mapping des étudiants
        $this->command->info('📋 Chargement du mapping détaillé des étudiants...');
        $this->buildStudentDetailedMapping();
        $this->command->info("   ✓ {$this->stats['total']} entrées d'étudiants mappées");
        $this->command->newLine();

        // Étape 2: Extraire les paiements
        $this->command->info('🔍 Extraction des paiements...');
        $payments = $this->extractPaymentsFromDB();

        if (empty($payments)) {
            $this->command->warn('Aucun paiement à migrer');
            return;
        }

        $totalPayments = count($payments);
        $this->command->info("📊 {$totalPayments} paiements trouvés");
        $this->command->newLine();

        // Réinitialiser le compteur total pour les paiements
        $this->stats['total'] = 0;

        // Regrouper les paiements par identité (PersonalInformation) + filiere_id
        $paymentsByIdentityFiliere = [];
        foreach ($payments as $payment) {
            $etudiantId = $payment['etudiant_id'];
            $studentDetails = $this->studentDetailedMapping[$etudiantId] ?? null;

            if (!$studentDetails) {
                continue;
            }

            // Trouver le PersonalInformation via identité
            $personalInfoId = $this->findPersonalInformation($studentDetails);

            if (!$personalInfoId) {
                $this->stats['identity_not_found']++;
                $this->errors[] = [
                    'reference' => $payment['reference'] ?? 'N/A',
                    'nom' => $studentDetails['nom'] . ' ' . $studentDetails['prenoms'],
                    'matricule' => $studentDetails['matricule'],
                    'error' => 'PersonalInformation introuvable',
                ];
                continue;
            }

            // Regrouper par PersonalInformation + filiere_id
            $key = $personalInfoId . '_' . $studentDetails['filiere_id'];
            if (!isset($paymentsByIdentityFiliere[$key])) {
                $paymentsByIdentityFiliere[$key] = [
                    'personal_info_id' => $personalInfoId,
                    'filiere_id' => $studentDetails['filiere_id'],
                    'annee_entree' => $studentDetails['annee_entree'],
                    'payments' => [],
                ];
            }
            $paymentsByIdentityFiliere[$key]['payments'][] = $payment;
        }

        $this->command->info("👥 Regroupés en " . count($paymentsByIdentityFiliere) . " groupes (identité + formation)");
        if ($this->stats['identity_not_found'] > 0) {
            $this->command->warn("⚠️  {$this->stats['identity_not_found']} paiements sans identité trouvée");
        }
        $this->command->newLine();

        $bar = $this->command->getOutput()->createProgressBar(count($paymentsByIdentityFiliere));
        $bar->start();

        foreach ($paymentsByIdentityFiliere as $group) {
            try {
                $personalInfoId = $group['personal_info_id'];
                $filiereId = $group['filiere_id'];
                $anneeEntree = $group['annee_entree'];
                $groupPayments = $group['payments'];

                // Récupérer le matricule depuis le premier paiement du groupe
                $firstPayment = $groupPayments[0] ?? null;
                if (!$firstPayment) {
                    continue;
                }

                $etudiantId = $firstPayment['etudiant_id'];
                $studentDetails = $this->studentDetailedMapping[$etudiantId] ?? null;

                if (!$studentDetails) {
                    throw new \Exception("Détails étudiant introuvables pour etudiant_id {$etudiantId}");
                }

                if (empty($studentDetails['matricule'])) {
                    throw new \Exception("Matricule vide pour etudiant_id {$etudiantId}");
                }

                $matricule = $studentDetails['matricule'];

                // Trier les paiements par date
                usort($groupPayments, function($a, $b) {
                    $dateA = $a['date_versement'] ?? '1970-01-01';
                    $dateB = $b['date_versement'] ?? '1970-01-01';
                    return strcmp($dateA, $dateB);
                });

                // Vérifier si c'est une filière Ingénierie
                $isIngenieurFiliere = $this->isIngenieurFiliere($filiereId);

                if ($isIngenieurFiliere) {
                    // Trouver les liens PREP et SPEC pour cette formation Ingénierie
                    $links = $this->findIngenieurLinks($matricule);

                    // Dispatcher les paiements selon la règle 425k
                    $dispatchedPayments = $this->dispatchPaymentsForIngenieur($groupPayments, $links);

                    // Créer chaque paiement dispatché
                    foreach ($dispatchedPayments as $payment) {
                        $this->migratePayment($payment);
                    }
                    $this->stats['ingenieur_payments'] += count($groupPayments);
                } else {
                    // Formation non-Ingénierie: attribution directe
                    $linkId = $this->findStudentPendingStudent(
                        $matricule,
                        $filiereId,
                        $anneeEntree
                    );

                    foreach ($groupPayments as $payment) {
                        $payment['_computed_link_id'] = $linkId;
                        $payment['_computed_amount'] = $payment['montant'];
                        $payment['_is_split'] = false;
                        $this->migratePayment($payment);
                    }
                    $this->stats['normal_payments'] += count($groupPayments);
                }

                $this->stats['success'] += count($groupPayments);

            } catch (\Exception $e) {
                $this->stats['errors'] += count($groupPayments ?? []);
                $this->errors[] = [
                    'reference' => $groupPayments[0]['reference'] ?? 'N/A',
                    'nom' => ($studentDetails['nom'] ?? '') . ' ' . ($studentDetails['prenoms'] ?? ''),
                    'matricule' => $studentDetails['matricule'] ?? 'N/A',
                    'personal_info_id' => $personalInfoId ?? 'N/A',
                    'filiere' => $filiereId ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->displayStats();

        // Exporter les erreurs dans un fichier si nécessaire
        if (!empty($this->errors)) {
            $this->exportErrors();
        }
    }

    /**
     * Construire le mapping détaillé avec identité complète depuis MySQL
     */
    private function buildStudentDetailedMapping(): void
    {
        $students = $this->oldDb->table('etudiants')
            ->select('id', 'nom', 'prenoms', 'date_naissance', 'lieu_de_naissance', 'matricule', 'filiere_id', 'annee_entree')
            ->get();

        foreach ($students as $oldStudent) {
            $oldId = (int) $oldStudent->id;
            $nom = trim($oldStudent->nom ?? '');
            $prenoms = trim($oldStudent->prenoms ?? '');
            $dateNaissance = $oldStudent->date_naissance ?? '';
            $lieuNaissance = trim($oldStudent->lieu_de_naissance ?? '');
            $matricule = trim($oldStudent->matricule ?? '');
            $filiereId = (int) ($oldStudent->filiere_id ?? 0);
            $anneeEntree = (int) ($oldStudent->annee_entree ?? 0);

            if ($oldId > 0) {
                $this->studentDetailedMapping[$oldId] = [
                    'nom' => $nom,
                    'prenoms' => $prenoms,
                    'date_naissance' => $dateNaissance,
                    'lieu_naissance' => $lieuNaissance,
                    'matricule' => $matricule,
                    'filiere_id' => $filiereId,
                    'annee_entree' => $anneeEntree,
                ];
                $this->stats['total']++;
            }
        }
    }

    /**
     * Extraire les paiements depuis MySQL
     */
    private function extractPaymentsFromDB(): array
    {
        return $this->oldDb->table('paiements')
            ->select('id', 'etudiant_id', 'montant', 'reference', 'numero_compte', 'date_versement',
                     'quittance', 'motif', 'observation', 'email', 'status', 'contact', 'created_at', 'updated_at')
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'etudiant_id' => $payment->etudiant_id,
                    'montant' => $payment->montant,
                    'reference' => $payment->reference,
                    'numero_compte' => $payment->numero_compte,
                    'date_versement' => $payment->date_versement,
                    'quittance' => $payment->quittance,
                    'motif' => $payment->motif,
                    'observation' => $payment->observation,
                    'email' => $payment->email,
                    'status' => $payment->status,
                    'contact' => $payment->contact,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
            })
            ->toArray();
    }

    /**
     * Extraire les étudiants du SQL
     */
    private function extractStudentsFromSQL(): array
    {
        $sqlContent = file_get_contents($this->sqlFile);

        // Trouver le début de INSERT INTO etudiants
        $start = strpos($sqlContent, 'INSERT INTO `etudiants`');
        if ($start === false) {
            return [];
        }

        // Trouver la fin (prochaine section)
        $end1 = strpos($sqlContent, 'CREATE TABLE', $start + 1);
        $end2 = strpos($sqlContent, 'INSERT INTO `etudiant_en_attentes`', $start + 1);
        $end = min($end1 ?: PHP_INT_MAX, $end2 ?: PHP_INT_MAX);

        if ($end === PHP_INT_MAX) {
            $studentsSection = substr($sqlContent, $start);
        } else {
            $studentsSection = substr($sqlContent, $start, $end - $start);
        }

        // Extraire toutes les lignes qui commencent par "("
        preg_match_all('/^\(.*?\)[,;]/m', $studentsSection, $rows);

        $students = [];
        foreach ($rows[0] as $row) {
            $row = rtrim($row, ',;');
            $values = $this->parseStudentRow($row);
            if ($values) {
                $students[] = $values;
            }
        }

        return $students;
    }

    /**
     * Parser une ligne d'étudiant avec toutes les infos nécessaires
     */
    private function parseStudentRow(string $row): ?array
    {
        // Nettoyer les parenthèses
        $row = trim($row, '() ');

        $values = str_getcsv($row, ',', "'");

        if (count($values) < 5) {
            return null;
        }

        return [
            'id' => !empty($values[0]) ? (int) trim($values[0]) : 0,
            'nom' => !empty($values[1]) && $values[1] !== 'NULL' ? trim($values[1]) : null,
            'prenoms' => !empty($values[2]) && $values[2] !== 'NULL' ? trim($values[2]) : null,
            'matricule' => !empty($values[4]) && $values[4] !== 'NULL' ? trim($values[4]) : null,
            'date_naissance' => !empty($values[5]) && $values[5] !== 'NULL' ? trim($values[5]) : null,
            'lieu_naissance' => !empty($values[6]) && $values[6] !== 'NULL' ? trim($values[6]) : null,
            'filiere_id' => !empty($values[13]) ? (int) trim($values[13]) : 0,
            'annee_entree' => !empty($values[15]) ? (int) trim($values[15]) : 0,
        ];
    }

    /**
     * Extraire les paiements du SQL
     */
    private function extractPaymentsFromSQL(): array
    {
        $sqlContent = file_get_contents($this->sqlFile);

        // Trouver le début de INSERT INTO paiements
        $start = strpos($sqlContent, 'INSERT INTO `paiements`');
        if ($start === false) {
            return [];
        }

        // Trouver la fin (prochaine section)
        $end1 = strpos($sqlContent, 'CREATE TABLE', $start + 1);
        $end2 = strpos($sqlContent, 'INSERT INTO `password_reset_tokens`', $start + 1);
        $end = min($end1 ?: PHP_INT_MAX, $end2 ?: PHP_INT_MAX);

        if ($end === PHP_INT_MAX) {
            $paymentsSection = substr($sqlContent, $start);
        } else {
            $paymentsSection = substr($sqlContent, $start, $end - $start);
        }

        // Extraire toutes les lignes qui commencent par "("
        preg_match_all('/^\(.*?\)[,;]/m', $paymentsSection, $rows);

        $payments = [];
        foreach ($rows[0] as $row) {
            $row = rtrim($row, ',;');
            $values = $this->parsePaymentRow($row);
            if ($values) {
                $payments[] = $values;
            }
        }

        return $payments;
    }

    /**
     * Extraire les lignes de valeurs (méthode helper non utilisée - peut être supprimée)
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

    /**
     * Parser une ligne de paiement
     */
    private function parsePaymentRow(string $row): ?array
    {
        // Nettoyer les parenthèses
        $row = trim($row, '() ');

        // Format: id, etudiant_id, montant, reference, numero_compte, date_versement,
        //         quittance, motif, observation, email, status, contact, created_at, updated_at
        $values = str_getcsv($row, ',', "'");

        if (count($values) < 11) {
            return null;
        }

        return [
            'id' => (int) trim($values[0]),
            'etudiant_id' => (int) trim($values[1]),
            'montant' => !empty($values[2]) && $values[2] !== 'NULL' ? (float) $values[2] : null,
            'reference' => trim($values[3], "'"),
            'numero_compte' => trim($values[4], "'"),
            'date_versement' => !empty($values[5]) && $values[5] !== 'NULL' ? trim($values[5], "'") : null,
            'quittance' => !empty($values[6]) && $values[6] !== 'NULL' ? trim($values[6], "'") : null,
            'motif' => trim($values[7], "'"),
            'observation' => !empty($values[8]) && $values[8] !== 'NULL' ? trim($values[8], "'") : null,
            'email' => !empty($values[9]) && $values[9] !== 'NULL' ? trim($values[9], "'") : null,
            'status' => trim($values[10], "'"),
            'contact' => !empty($values[11]) && $values[11] !== 'NULL' ? trim($values[11], "'") : null,
            'created_at' => !empty($values[12]) && $values[12] !== 'NULL' ? trim($values[12], "'") : null,
            'updated_at' => !empty($values[13]) && $values[13] !== 'NULL' ? trim($values[13], "'") : null,
        ];
    }

    /**
     * Migrer un paiement individuel
     */
    private function migratePayment(array $oldPayment): void
    {
        $this->stats['total']++;

        // Vérifier si le paiement existe déjà
        $existingPayment = Paiement::where('reference', $oldPayment['reference'])->first();

        if ($existingPayment) {
            $this->stats['skipped']++;
            return;
        }

        // Récupérer les détails de l'étudiant
        $studentDetails = $this->studentDetailedMapping[$oldPayment['etudiant_id']] ?? null;

        if (!$studentDetails || empty($studentDetails['matricule'])) {
            throw new \Exception("Matricule introuvable pour etudiant_id {$oldPayment['etudiant_id']}");
        }

        $matricule = $studentDetails['matricule'];

        // Trouver le student
        $student = Student::where('student_id_number', $matricule)->first();

        if (!$student) {
            throw new \Exception("Student introuvable pour matricule {$matricule}");
        }

        // Mapper le status - TOUS les paiements validés retournent en attente
        $status = match($oldPayment['status']) {
            'attente' => 'pending',
            'accepte' => 'pending', // ⚠️ Quittances validées ramenées en attente
            'rejete' => 'rejected',
            default => 'pending',
        };

        // Créer le paiement (sera dispatché correctement par la méthode run)
        Paiement::create([
            'uuid' => Str::uuid(),
            'student_id_number' => $matricule,
            'student_pending_student_id' => $oldPayment['_computed_link_id'], // Calculé au préalable
            'reference' => $oldPayment['reference'],
            'amount' => $oldPayment['_computed_amount'], // Montant calculé (peut être splitté)
            'account_number' => $oldPayment['numero_compte'],
            'payment_date' => $oldPayment['date_versement'] ? Carbon::parse($oldPayment['date_versement']) : now(),
            'purpose' => $oldPayment['motif'] . ($oldPayment['_is_split'] ? ' (Part ' . $oldPayment['_split_part'] . ')' : ''),
            'email' => $oldPayment['email'],
            'contact' => $oldPayment['contact'],
            'receipt_path' => $oldPayment['quittance'],
            'status' => $status,
            'observation' => $oldPayment['observation'],
            'created_at' => $oldPayment['created_at'] ? Carbon::parse($oldPayment['created_at']) : now(),
            'updated_at' => $oldPayment['updated_at'] ? Carbon::parse($oldPayment['updated_at']) : now(),
        ]);
    }

    /**
     * Trouver PersonalInformation par identité (case-insensitive)
     */
    private function findPersonalInformation(array $studentDetails): ?int
    {
        // Créer une clé de cache
        $cacheKey = mb_strtolower($studentDetails['nom']) . '_' . mb_strtolower($studentDetails['prenoms']) . '_' . $studentDetails['date_naissance'];

        // Vérifier le cache
        if (isset($this->personalInfoCache[$cacheKey])) {
            return $this->personalInfoCache[$cacheKey];
        }

        // Recherche case-insensitive comme dans StudentIdService
        $pi = \App\Modules\Inscription\Models\PersonalInformation::query()
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($studentDetails['nom'])])
            ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($studentDetails['prenoms'])])
            ->whereDate('birth_date', $studentDetails['date_naissance']);

        // Ajouter le lieu de naissance si disponible
        if (!empty($studentDetails['lieu_naissance'])) {
            $pi->whereRaw('LOWER(birth_place) = ?', [mb_strtolower($studentDetails['lieu_naissance'])]);
        }

        $personalInfo = $pi->first();

        // Si pas trouvé et lieu fourni, essayer sans le lieu
        if (!$personalInfo && !empty($studentDetails['lieu_naissance'])) {
            $personalInfo = \App\Modules\Inscription\Models\PersonalInformation::query()
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($studentDetails['nom'])])
                ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($studentDetails['prenoms'])])
                ->whereDate('birth_date', $studentDetails['date_naissance'])
                ->first();
        }

        // Fallback: Chercher via matricule
        if (!$personalInfo && !empty($studentDetails['matricule'])) {
            $student = Student::where('student_id_number', $studentDetails['matricule'])->first();
            if ($student) {
                $personalInfo = $student->pendingStudents->first()?->personalInformation;
            }
        }

        $result = $personalInfo ? $personalInfo->id : null;

        // Mettre en cache
        $this->personalInfoCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Trouver le Student via PersonalInformation
     */
    private function findStudentByPersonalInfo(int $personalInfoId): ?Student
    {
        $personalInfo = \App\Modules\Inscription\Models\PersonalInformation::find($personalInfoId);

        if (!$personalInfo) {
            return null;
        }

        // Trouver le Student via PendingStudent
        $pendingStudent = $personalInfo->pendingStudents()->first();

        if (!$pendingStudent) {
            return null;
        }

        // Récupérer le Student via StudentPendingStudent
        $studentPendingStudent = $pendingStudent->studentPendingStudents()->first();

        return $studentPendingStudent?->student;
    }

    /**
     * Vérifier si un filiere_id correspond à Ingénierie
     */
    private function isIngenieurFiliere(int $filiereId): bool
    {
        // Filières Ingénierie dans l'ancienne BDD
        $ingenieurFilieres = [44, 45, 46, 47]; // GC, GT, GE, GME Ingénierie
        return in_array($filiereId, $ingenieurFilieres);
    }

    /**
     * Trouver les liens PREP et SPEC pour un étudiant Ingénieur
     */
    private function findIngenieurLinks(string $matricule): array
    {
        $prepLink = null;
        $specLink = null;

        // Trouver le student
        $student = Student::where('student_id_number', $matricule)->first();
        if (!$student) {
            return ['prep' => null, 'spec' => null];
        }

        // Récupérer tous les StudentPendingStudent d'Ingénierie
        $links = StudentPendingStudent::where('student_id', $student->id)
            ->with('pendingStudent.department.cycle')
            ->get();

        foreach ($links as $link) {
            $department = $link->pendingStudent?->department;
            $cycle = $department?->cycle;
            $abbreviation = $department?->abbreviation ?? '';

            // Vérifier si c'est un cycle Ingénierie
            $isIngenieur = $cycle && (stripos($cycle->name, 'Ingé') !== false || stripos($cycle->name, 'Ing') !== false);

            if ($isIngenieur) {
                // Prépa = département commence par P- (P-GC, P-GT, P-GE, P-GME)
                if (str_starts_with($abbreviation, 'P-')) {
                    $prepLink = $link;
                } else {
                    // Spécialité = GC, GT, GE, GME (sans P-)
                    $specLink = $link;
                }
            }
        }

        return [
            'prep' => $prepLink,
            'spec' => $specLink,
        ];
    }

    /**
     * Dispatcher les paiements pour Ingénierie - TOUT VA SUR PRÉPA (pas de split)
     */
    private function dispatchPaymentsForIngenieur(array $payments, array $links): array
    {
        $prepLink = $links['prep'];
        $specLink = $links['spec'];

        // Si pas de lien PREP, utiliser SPEC comme fallback
        $targetLink = $prepLink ?: $specLink;

        if (!$targetLink) {
            // Aucun lien trouvé, retourner les paiements sans modification
            foreach ($payments as &$payment) {
                $payment['_computed_link_id'] = null;
                $payment['_computed_amount'] = $payment['montant'];
                $payment['_is_split'] = false;
            }
            return $payments;
        }

        // TOUT va sur PRÉPA (ou SPEC si pas de PREP)
        foreach ($payments as &$payment) {
            $payment['_computed_link_id'] = $targetLink->id;
            $payment['_computed_amount'] = $payment['montant'];
            $payment['_is_split'] = false;
        }

        return $payments;
    }

    /**
     * Trouver le StudentPendingStudent approprié basé sur matricule, filiere_id et annee_entree
     */
    private function findStudentPendingStudent(string $matricule, int $filiereId, int $anneeEntree): ?int
    {
        // Trouver le student
        $student = Student::where('student_id_number', $matricule)->first();
        if (!$student) {
            return null;
        }

        // Mapper l'ancien filiere_id vers le nouveau département
        $departmentInfo = $this->mapFiliereToDepartment($filiereId);
        if (!$departmentInfo) {
            // Si pas de mapping trouvé, prendre le lien le plus récent
            $link = StudentPendingStudent::where('student_id', $student->id)
                ->orderBy('id', 'desc')
                ->first();
            return $link ? $link->id : null;
        }

        // Trouver le département dans la nouvelle BDD
        $department = \App\Modules\Inscription\Models\Department::where('name', $departmentInfo['nom'])
            ->whereHas('cycle', function($q) use ($departmentInfo) {
                $q->where('id', $this->getCycleIdByOldId($departmentInfo['cycle']));
            })
            ->first();

        if (!$department) {
            // Fallback: prendre le lien le plus récent
            $link = StudentPendingStudent::where('student_id', $student->id)
                ->orderBy('id', 'desc')
                ->first();
            return $link ? $link->id : null;
        }

        // Trouver le StudentPendingStudent correspondant
        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', function($q) use ($department) {
                $q->where('department_id', $department->id);
            })
            ->first();

        return $link ? $link->id : null;
    }

    /**
     * Mapper l'ancien filiere_id vers les infos du département
     */
    private function mapFiliereToDepartment(int $oldFiliereId): ?array
    {
        $mapping = [
            27 => ['nom' => 'Génie Civil', 'cycle' => 7],
            28 => ['nom' => 'Génie Electrique', 'cycle' => 7],
            29 => ['nom' => 'Géomètre Topographe', 'cycle' => 7],
            44 => ['nom' => 'Génie Civil', 'cycle' => 9],
            45 => ['nom' => 'Géomètre Topographe', 'cycle' => 9],
            46 => ['nom' => 'Génie Electrique', 'cycle' => 9],
            // Ajoutez d'autres mappings au besoin
        ];

        return $mapping[$oldFiliereId] ?? null;
    }

    /**
     * Mapper l'ancien cycle_id vers le nouveau
     */
    private function getCycleIdByOldId(int $oldCycleId): ?int
    {
        $cycleMapping = [
            7 => \App\Modules\Inscription\Models\Cycle::where('name', 'LIKE', '%Licence%')->first()?->id,
            9 => \App\Modules\Inscription\Models\Cycle::where('name', 'LIKE', '%Ingé%')->first()?->id,
        ];

        return $cycleMapping[$oldCycleId] ?? null;
    }

    /**
     * Exporter les erreurs dans un fichier CSV
     */
    private function exportErrors(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = database_path("../payment_migration_errors_{$timestamp}.csv");

        $file = fopen($filename, 'w');

        // En-têtes CSV
        fputcsv($file, [
            'Référence',
            'Nom Étudiant',
            'Matricule',
            'Personal Info ID',
            'Filière ID',
            'Erreur',
        ]);

        // Lignes d'erreurs
        foreach ($this->errors as $error) {
            fputcsv($file, [
                $error['reference'] ?? 'N/A',
                $error['nom'] ?? 'N/A',
                $error['matricule'] ?? 'N/A',
                $error['personal_info_id'] ?? 'N/A',
                $error['filiere'] ?? 'N/A',
                $error['error'] ?? 'N/A',
            ]);
        }

        fclose($file);

        $this->command->newLine();
        $this->command->warn("📄 Fichier d'erreurs exporté: {$filename}");
        $this->command->info("   " . count($this->errors) . " erreurs détaillées");
    }

    /**
     * Afficher les statistiques
     */
    private function displayStats(): void
    {
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║           STATISTIQUES MIGRATION PAIEMENTS                    ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // Compter les lignes créées dans la nouvelle BDD
        $newCount = \App\Modules\Finance\Models\Paiement::count();

        $this->command->info("📊 ANCIENNE BDD: 481 paiements");
        $this->command->info("📊 NOUVELLE BDD: {$newCount} lignes créées");
        $this->command->newLine();

        $this->command->info("📋 Détails de la migration:");
        $this->command->info("   ✅ Paiements traités: {$this->stats['success']}");
        $this->command->info("   🎓 Paiements Ingénierie (sur Prépa): {$this->stats['ingenieur_payments']}");
        $this->command->info("   📚 Paiements normaux (autres filières): {$this->stats['normal_payments']}");
        $this->command->info("   ⏭️  Ignorés (déjà existants): {$this->stats['skipped']}");

        if ($this->stats['errors'] > 0) {
            $this->command->error("   ❌ Erreurs: {$this->stats['errors']}");
        } else {
            $this->command->info("   ✅ Aucune erreur");
        }

        $this->command->newLine();

        if (!empty($this->errors)) {
            $this->command->warn("⚠️  Premières erreurs:");
            foreach (array_slice($this->errors, 0, 10) as $error) {
                $nom = $error['nom'] ?? 'N/A';
                $ref = $error['reference'] ?? 'N/A';
                $matricule = $error['matricule'] ?? 'N/A';
                $message = $error['error'] ?? 'Erreur inconnue';
                $this->command->line("   - {$nom} (Ref: {$ref}, Matricule: {$matricule}): {$message}");
            }
        }
    }
}

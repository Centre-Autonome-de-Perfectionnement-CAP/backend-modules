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
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\Student;
use Carbon\Carbon;

/**
 * Seeder de test pour migrer seulement 5 étudiants
 * Permet de valider la migration avant de tout migrer
 */
class TestMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧪 TEST DE MIGRATION - 5 étudiants seulement');
        $this->command->newLine();

        // Vérifier les prérequis
        if (!$this->checkPrerequisites()) {
            return;
        }

        // Étudiants de test (extraits manuellement de l'ancienne DB)
        $testStudents = [
            [
                'nom' => 'SOVI-GUIDI',
                'prenoms' => 'Wilfried S.',
                'matricule' => '2060903',
                'date_naissance' => '1973-07-09',
                'lieu_de_naissance' => 'PORTO-NOVO',
                'pays_de_naissance' => 'Bénin',
                'genre' => 'masculin',
                'email' => null,
                'filiere_id' => 28, // Génie Electrique
                'diplome_entree_id' => 1,
                'annee_entree' => 2002,
                'contacts' => [''],
            ],
            [
                'nom' => 'KINDOZOUN',
                'prenoms' => 'S. Rodrigue',
                'matricule' => '1912603',
                'date_naissance' => '1980-06-20',
                'lieu_de_naissance' => 'TORI-BOSSITO',
                'pays_de_naissance' => 'Bénin',
                'genre' => 'masculin',
                'email' => null,
                'filiere_id' => 27, // Génie Civil
                'diplome_entree_id' => 1,
                'annee_entree' => 2002,
                'contacts' => [''],
            ],
            [
                'nom' => 'WANVOEKE',
                'prenoms' => 'G. S. Arsène',
                'matricule' => '2015001',
                'date_naissance' => '1976-07-22',
                'lieu_de_naissance' => 'COVE',
                'pays_de_naissance' => 'Bénin',
                'genre' => 'masculin',
                'email' => null,
                'filiere_id' => 47, // Génie Mécanique et Energétique
                'diplome_entree_id' => 1,
                'annee_entree' => 2002,
                'contacts' => [''],
            ],
            [
                'nom' => 'APITHY Ep. OWOLABI',
                'prenoms' => 'Martine',
                'matricule' => '1010601',
                'date_naissance' => '1957-01-07',
                'lieu_de_naissance' => 'PORTO-NOVO',
                'pays_de_naissance' => 'Bénin',
                'genre' => 'feminin',
                'email' => null,
                'filiere_id' => 31, // Production Végétale
                'diplome_entree_id' => 1,
                'annee_entree' => 2002,
                'contacts' => [''],
            ],
            [
                'nom' => 'ADAM I.',
                'prenoms' => 'Sahadatou',
                'matricule' => '2060003',
                'date_naissance' => '1977-01-15',
                'lieu_de_naissance' => 'NIKKI',
                'pays_de_naissance' => 'Bénin',
                'genre' => 'feminin',
                'email' => null,
                'filiere_id' => 31, // Production Végétale
                'diplome_entree_id' => 1,
                'annee_entree' => 2002,
                'contacts' => [''],
            ],
        ];

        $success = 0;
        $errors = 0;

        foreach ($testStudents as $student) {
            try {
                $this->migrateStudent($student);
                $success++;
                $this->command->info('✅ ' . $student['matricule'] . ' - ' . $student['nom'] . ' ' . $student['prenoms']);
            } catch (\Exception $e) {
                $errors++;
                $this->command->error('❌ ' . $student['matricule'] . ' - ' . $e->getMessage());
            }
        }

        $this->command->newLine();
        $this->command->info('📊 Résultats:');
        $this->command->info('   ✅ Succès: ' . $success . '/5');
        $this->command->info('   ❌ Erreurs: ' . $errors . '/5');

        if ($success === 5) {
            $this->command->newLine();
            $this->command->info('🎉 TEST RÉUSSI ! Vous pouvez lancer la migration complète avec:');
            $this->command->info('   php artisan db:seed --class=MigrationDatabaseSeeder');
        }
    }

    /**
     * Vérifier les prérequis
     */
    private function checkPrerequisites(): bool
    {
        $this->command->info('🔍 Vérification des prérequis...');

        // Vérifier que les cycles existent
        $cyclesCount = Cycle::count();
        if ($cyclesCount === 0) {
            $this->command->error('❌ Aucun cycle trouvé. Exécutez d\'abord: php artisan db:seed --class=OldDatabaseMigrationSeeder');
            return false;
        }
        $this->command->info('   ✓ Cycles: ' . $cyclesCount);

        // Vérifier que les départements existent
        $deptCount = Department::count();
        if ($deptCount === 0) {
            $this->command->error('❌ Aucun département trouvé. Exécutez d\'abord: php artisan db:seed --class=OldDatabaseMigrationSeeder');
            return false;
        }
        $this->command->info('   ✓ Départements: ' . $deptCount);

        // Vérifier que les années académiques existent
        $yearCount = AcademicYear::count();
        if ($yearCount === 0) {
            $this->command->error('❌ Aucune année académique trouvée. Exécutez d\'abord: php artisan db:seed --class=OldDatabaseMigrationSeeder');
            return false;
        }
        $this->command->info('   ✓ Années académiques: ' . $yearCount);

        // Vérifier que les diplômes existent
        $diplomaCount = EntryDiploma::count();
        if ($diplomaCount === 0) {
            $this->command->error('❌ Aucun diplôme d\'entrée trouvé. Exécutez d\'abord: php artisan db:seed --class=OldDatabaseMigrationSeeder');
            return false;
        }
        $this->command->info('   ✓ Diplômes: ' . $diplomaCount);

        $this->command->info('   ✅ Tous les prérequis sont remplis');
        $this->command->newLine();

        return true;
    }

    /**
     * Migrer un étudiant
     */
    private function migrateStudent(array $oldStudent): void
    {
        // Vérifier si l'étudiant existe déjà
        $existingStudent = Student::where('student_id_number', $oldStudent['matricule'])->first();
        if ($existingStudent) {
            throw new \Exception('Étudiant déjà migré');
        }

        DB::beginTransaction();

        try {
            // 1. Créer PersonalInformation
            $personalInfo = PersonalInformation::create([
                'last_name' => $oldStudent['nom'],
                'first_names' => $oldStudent['prenoms'],
                'email' => $oldStudent['email'],
                'birth_date' => Carbon::parse($oldStudent['date_naissance']),
                'birth_place' => $oldStudent['lieu_de_naissance'],
                'birth_country' => $oldStudent['pays_de_naissance'],
                'gender' => $oldStudent['genre'] === 'masculin' ? 'M' : 'F',
                'contacts' => array_filter($oldStudent['contacts']),
                'nationality' => 'Béninoise',
                'photo' => null,
            ]);

            // 2. Trouver le département
            $department = $this->findDepartment($oldStudent['filiere_id']);
            if (!$department) {
                throw new \Exception('Département introuvable pour filiere_id: ' . $oldStudent['filiere_id']);
            }

            // 3. Trouver l'année académique
            $academicYear = AcademicYear::where('academic_year', 'like', $oldStudent['annee_entree'] . '-%')->first();
            if (!$academicYear) {
                throw new \Exception('Année académique introuvable: ' . $oldStudent['annee_entree']);
            }

            // 4. Trouver le diplôme
            $diploma = EntryDiploma::where('name', 'Baccalauréat ou Équivalent')->first();

            // 5. Créer PendingStudent
            $pendingStudent = PendingStudent::create([
                'tracking_code' => 'TEST-' . $oldStudent['matricule'],
                'personal_information_id' => $personalInfo->id,
                'academic_year_id' => $academicYear->id,
                'department_id' => $department->id,
                'entry_diploma_id' => $diploma?->id,
                'level' => 'Licence 1',
                'status' => 'approved',
                'cuca_opinion' => 'favorable',
                'cuo_opinion' => 'favorable',
                'sponsorise' => 'Non',
            ]);

            // 6. Créer Student
            $student = Student::create([
                'student_id_number' => $oldStudent['matricule'],
                'password' => Hash::make($oldStudent['matricule']),
            ]);

            // 7. Créer le lien StudentPendingStudent
            $link = StudentPendingStudent::create([
                'student_id' => $student->id,
                'pending_student_id' => $pendingStudent->id,
            ]);

            // 8. Créer AcademicPath
            AcademicPath::create([
                'student_pending_student_id' => $link->id,
                'academic_year_id' => $academicYear->id,
                'study_level' => 'Licence 1',
                'year_decision' => null,
                'financial_status' => 'Non exonéré',
                'cohort' => (string) $oldStudent['annee_entree'],
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Trouver le département
     */
    private function findDepartment(int $oldFiliereId): ?Department
    {
        $filiereMapping = [
            27 => ['nom' => 'Génie Civil', 'cycle' => 'Licence Professionnelle'],
            28 => ['nom' => 'Génie Electrique', 'cycle' => 'Licence Professionnelle'],
            29 => ['nom' => 'Géomètre Topographe', 'cycle' => 'Licence Professionnelle'],
            30 => ['nom' => 'Production Animale', 'cycle' => 'Licence Professionnelle'],
            31 => ['nom' => 'Production Végétale', 'cycle' => 'Licence Professionnelle'],
            47 => ['nom' => 'Génie Mécanique et Energétique', 'cycle' => 'Licence Professionnelle'],
        ];

        if (!isset($filiereMapping[$oldFiliereId])) {
            return null;
        }

        $filiere = $filiereMapping[$oldFiliereId];
        $cycle = Cycle::where('name', $filiere['cycle'])->first();

        if (!$cycle) {
            return null;
        }

        return Department::where('name', $filiere['nom'])
            ->where('cycle_id', $cycle->id)
            ->first();
    }
}

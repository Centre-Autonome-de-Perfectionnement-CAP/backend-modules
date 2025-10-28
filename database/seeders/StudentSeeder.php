<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\EntryDiploma;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        // Récupération des données de référence
        $academicYears = AcademicYear::orderBy('year_start', 'desc')->get();
        $currentYear = $academicYears->where('is_current', true)->first();
        $departments = Department::all();
        $entryDiplomas = EntryDiploma::all();

        if ($academicYears->isEmpty() || $departments->isEmpty() || $entryDiplomas->isEmpty()) {
            $this->command->warn('⚠️  Données de référence manquantes. Exécutez les autres seeders d\'abord.');
            return;
        }

        // Générer des données d'étudiants
        $lastNames = ['KOUASSI', 'KONE', 'TRAORE', 'YAO', 'KOUAME', 'TOURE', 'OUATTARA', 'N\'GUESSAN', 'BAMBA', 'DIALLO'];
        $firstNamesMale = ['Jean Marc', 'Amadou', 'Kouadio', 'Yao', 'Kofi', 'Kouassi', 'N\'Goran', 'Sekou'];
        $firstNamesFemale = ['Marie Ange', 'Aya', 'Adjoua', 'Akissi', 'Fatou', 'Aminata', 'Aïcha'];
        $cities = ['Abidjan', 'Bouaké', 'Yamoussoukro', 'Daloa', 'San-Pédro', 'Korhogo', 'Man'];
        $levels = ['L1', 'L2', 'L3'];
        
        $studentsData = [];
        $studentNumber = 1;

        // Générer des étudiants pour chaque année académique
        foreach ($academicYears as $academicYear) {
            $isCurrentYear = $academicYear->is_current;
            $yearPrefix = substr($academicYear->academic_year, 2, 2); // Ex: "24" pour 2024-2025
            
            // Créer 8 étudiants par année académique (mix de niveaux)
            for ($i = 0; $i < 20; $i++) {
                $gender = ($i % 2 == 0) ? 'M' : 'F';
                $firstName = $gender == 'M' ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
                $lastName = $lastNames[array_rand($lastNames)];
                $city = $cities[array_rand($cities)];
                $level = $levels[$i % 3];
                
                $studentIdNumber = $yearPrefix . str_pad($studentNumber, 6, '0', STR_PAD_LEFT);
                
                $data = [
                    'personal_info' => [
                        'last_name' => $lastName,
                        'first_names' => $firstName,
                        'email' => 'etudiant' . $studentNumber . '@cap.edu',
                        'birth_date' => '200' . rand(0, 3) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                        'birth_place' => $city,
                        'birth_country' => 'Côte d\'Ivoire',
                        'gender' => $gender,
                        'contacts' => json_encode(['phone' => '+225 070' . rand(1000000, 9999999)]),
                        'nationality' => 'Ivoirienne',
                    ],
                    'student_id_number' => $studentIdNumber,
                    'level' => $level,
                    'academic_year' => $academicYear,
                    'department' => $departments->random(),
                    'entry_diploma' => $entryDiplomas->random(),
                    'is_current_year' => $isCurrentYear,
                ];
                
                $studentsData[] = $data;
                $studentNumber++;
            }
        }

        // Traiter les étudiants
        foreach ($studentsData as $data) {
            // 1. Créer PersonalInformation
            $personalInfo = PersonalInformation::create(array_merge(
                $data['personal_info'],
                ['entry_diploma_id' => $data['entry_diploma']->id]
            ));

            // 2. Créer PendingStudent
            $pendingStatus = $data['is_current_year'] ? 'pending' : 'approved';
            $pendingStudent = PendingStudent::create([
                'personal_information_id' => $personalInfo->id,
                'tracking_code' => 'PS-' . strtoupper(Str::random(8)),
                'academic_year_id' => $data['academic_year']->id,
                'department_id' => $data['department']->id,
                'entry_diploma_id' => $data['entry_diploma']->id,
                'level' => $data['level'],
                'status' => $pendingStatus,
                'cuca_opinion' => $data['is_current_year'] ? null : 'favorable',
                'cuca_comment' => $data['is_current_year'] ? null : 'Dossier complet et conforme',
            ]);

            // 3 & 4. Pour les années précédentes: créer Student et la relation
            if (!$data['is_current_year']) {
                $student = Student::create([
                    'student_id_number' => $data['student_id_number'],
                    'password' => Hash::make($data['student_id_number']),
                ]);

                StudentPendingStudent::create([
                    'student_id' => $student->id,
                    'pending_student_id' => $pendingStudent->id,
                ]);
                
                $status = "✅ Étudiant approuvé";
            } else {
                $status = "⏳ Candidature en attente";
            }

            $this->command->info("{$status}: {$data['student_id_number']} - {$data['personal_info']['first_names']} {$data['personal_info']['last_name']} ({$data['academic_year']->academic_year})");
        }

        $this->command->info('');
        $this->command->info('✅ Tous les étudiants créés avec succès!');
        $this->command->info("📊 Total: " . count($studentsData) . " étudiants sur " . $academicYears->count() . " années académiques");
        $this->command->warn('');
        $this->command->warn('📧 COMPTES ÉTUDIANTS (années précédentes):');
        $this->command->warn('Format: Matricule | Mot de passe (même que le matricule)');
        $this->command->warn('⏳ Les étudiants de l\'année courante sont en attente d\'approbation');
    }
}

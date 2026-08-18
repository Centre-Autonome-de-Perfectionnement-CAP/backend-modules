<?php

namespace Database\Seeders;

use App\Models\LegacyStudent;
use App\Modules\Inscription\Models\Department;
use Illuminate\Database\Seeder;

class LegacyStudentSeeder extends Seeder
{
    public function run(): void
    {
        // Récupère jusqu'à 4 filières existantes pour varier les rattachements.
        // Si la table `departments` est vide, lance DepartmentSeeder avant celui-ci.
        $departmentIds = Department::query()->limit(4)->pluck('id');

        if ($departmentIds->isEmpty()) {
            $this->command?->warn(
                'Aucune filière trouvée dans `departments` — lancez DepartmentSeeder avant LegacyStudentSeeder pour avoir les rattachements multi-filières.'
            );
        }

        $students = [
            ['matricule' => 'CAP-2018-001', 'first_name' => 'Ama', 'last_name' => 'Koudjo', 'email' => 'ama.koudjo@example.com', 'enrollment_year' => 2018, 'status' => 'validated'],
            ['matricule' => 'CAP-2019-014', 'first_name' => 'Fiacre', 'last_name' => 'Dossou', 'email' => 'fiacre.dossou@example.com', 'enrollment_year' => 2019, 'status' => 'pending'],
            ['matricule' => 'CAP-2017-032', 'first_name' => 'Chimène', 'last_name' => 'Agbo', 'email' => 'chimene.agbo@example.com', 'enrollment_year' => 2017, 'status' => 'rejected', 'rejection_reason' => 'Matricule introuvable dans les archives papier.'],
            ['matricule' => 'CAP-2020-007', 'first_name' => 'Réal', 'last_name' => 'Sossou', 'email' => 'real.sossou@example.com', 'enrollment_year' => 2020, 'status' => 'validated'],
            ['matricule' => 'CAP-2016-045', 'first_name' => 'Nadège', 'last_name' => 'Houngbo', 'email' => 'nadege.houngbo@example.com', 'enrollment_year' => 2016, 'status' => 'pending'],
            ['matricule' => 'CAP-2021-002', 'first_name' => 'Judicaël', 'last_name' => 'Tossou', 'email' => 'judicael.tossou@example.com', 'enrollment_year' => 2021, 'status' => 'validated'],
            ['matricule' => 'CAP-2015-018', 'first_name' => 'Bérénice', 'last_name' => 'Aholou', 'email' => 'berenice.aholou@example.com', 'enrollment_year' => 2015, 'status' => 'rejected', 'rejection_reason' => 'Doublon avec un dossier déjà validé.'],
            ['matricule' => 'CAP-2022-011', 'first_name' => 'Landry', 'last_name' => 'Zannou', 'email' => 'landry.zannou@example.com', 'enrollment_year' => 2022, 'status' => 'pending'],
            ['matricule' => 'CAP-2019-055', 'first_name' => 'Ornella', 'last_name' => 'Codjo', 'email' => 'ornella.codjo@example.com', 'enrollment_year' => 2019, 'status' => 'validated'],
            ['matricule' => 'CAP-2018-060', 'first_name' => 'Ulrich', 'last_name' => 'Adjovi', 'email' => 'ulrich.adjovi@example.com', 'enrollment_year' => 2018, 'status' => 'pending'],
        ];

        foreach ($students as $index => $data) {
            /** @var LegacyStudent $legacyStudent */
            $legacyStudent = LegacyStudent::query()->create([
                'matricule' => $data['matricule'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => '+229 90 ' . str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                'enrollment_year' => $data['enrollment_year'],
                'status' => $data['status'],
                'rejection_reason' => $data['rejection_reason'] ?? null,
            ]);

            if ($departmentIds->isNotEmpty()) {
                // Alterne entre 1 et 2 filières par étudiant pour tester le multi-filières.
                $count = $index % 3 === 0 ? 2 : 1;
                $legacyStudent->departments()->attach(
                    $departmentIds->random(min($count, $departmentIds->count()))->all()
                );
            }
        }

        $this->command?->info('10 anciens étudiants créés (pending/validated/rejected, multi-filières).');
    }
}

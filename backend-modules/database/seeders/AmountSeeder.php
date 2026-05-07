<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Finance\Models\Amount;
use App\Modules\Cours\Models\Program;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Support\Str;

class AmountSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            $this->command->warn('⚠️  Aucune année académique active. Exécutez AcademicYearSeeder d\'abord.');
            return;
        }

        $programs = Program::all();
        
        if ($programs->isEmpty()) {
            $this->command->warn('⚠️  Aucun programme trouvé. Exécutez ProgramSeeder d\'abord.');
            return;
        }

        $amounts = [];

        // Frais Licence
        foreach ($departments->where('code', 'like', '%-L') as $dept) {
            $amounts[] = [
                'label' => "Frais d'inscription {$dept->name}",
                'amount' => 50000,
                'type' => 'inscription',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Frais d'inscription pour le cycle Licence",
            ];
            $amounts[] = [
                'label' => "Frais de scolarité {$dept->name} (1er semestre)",
                'amount' => 250000,
                'type' => 'scolarite',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Premier semestre - Licence",
            ];
            $amounts[] = [
                'label' => "Frais de scolarité {$dept->name} (2ème semestre)",
                'amount' => 250000,
                'type' => 'scolarite',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Deuxième semestre - Licence",
            ];
        }

        // Frais Master
        foreach ($departments->where('code', 'like', '%-M') as $dept) {
            $amounts[] = [
                'label' => "Frais d'inscription {$dept->name}",
                'amount' => 75000,
                'type' => 'inscription',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Frais d'inscription pour le cycle Master",
            ];
            $amounts[] = [
                'label' => "Frais de scolarité {$dept->name} (1er semestre)",
                'amount' => 350000,
                'type' => 'scolarite',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Premier semestre - Master",
            ];
            $amounts[] = [
                'label' => "Frais de scolarité {$dept->name} (2ème semestre)",
                'amount' => 350000,
                'type' => 'scolarite',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Deuxième semestre - Master",
            ];
        }

        // Frais Ingénieur
        foreach ($departments->where('code', 'like', '%-I') as $dept) {
            $amounts[] = [
                'label' => "Frais d'inscription {$dept->name}",
                'amount' => 100000,
                'type' => 'inscription',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Frais d'inscription pour le cycle Ingénieur",
            ];
            $amounts[] = [
                'label' => "Frais de scolarité {$dept->name} (1er semestre)",
                'amount' => 450000,
                'type' => 'scolarite',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Premier semestre - Ingénieur",
            ];
            $amounts[] = [
                'label' => "Frais de scolarité {$dept->name} (2ème semestre)",
                'amount' => 450000,
                'type' => 'scolarite',
                'department_id' => $dept->id,
                'academic_year_id' => $currentYear->id,
                'is_active' => true,
                'description' => "Deuxième semestre - Ingénieur",
            ];
        }

        // Frais communs
        $amounts[] = [
            'label' => 'Frais d\'examen',
            'amount' => 25000,
            'type' => 'examen',
            'academic_year_id' => $currentYear->id,
            'is_active' => true,
            'description' => 'Frais d\'examen pour tous les cycles',
        ];

        $amounts[] = [
            'label' => 'Frais de réinscription',
            'amount' => 30000,
            'type' => 'autre',
            'academic_year_id' => $currentYear->id,
            'is_active' => true,
            'description' => 'Frais pour les redoublants',
        ];

        foreach ($amounts as $amountData) {
            Amount::updateOrCreate(
                [
                    'label' => $amountData['label'],
                    'academic_year_id' => $amountData['academic_year_id'],
                ],
                $amountData
            );
        }

        $this->command->info('✅ Montants créés avec succès!');
        $this->command->info("📊 Total: " . count($amounts) . " montants créés");
    }
}

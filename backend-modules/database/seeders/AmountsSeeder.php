<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Finance\Models\Amount;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use Illuminate\Support\Str;

class AmountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Création des barèmes de frais...');
        $this->command->newLine();

        // Récupérer toutes les années académiques
        $academicYears = AcademicYear::all();
        
        if ($academicYears->isEmpty()) {
            $this->command->warn('⚠️  Aucune année académique trouvée. Veuillez d\'abord créer des années académiques.');
            return;
        }

        // Récupérer tous les départements
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->warn('⚠️  Aucun département trouvé. Veuillez d\'abord créer des départements.');
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($academicYears as $academicYear) {
            $this->command->info("📅 Année académique: {$academicYear->name}");
            
            foreach ($departments as $department) {
                $this->command->line("   🏫 Département: {$department->name}");
                
                // Déterminer le nombre de niveaux selon le cycle
                $levels = $this->getLevelsForDepartment($department);
                
                foreach ($levels as $level => $levelName) {
                    // Vérifier si le barème existe déjà
                    $exists = Amount::where('academic_year_id', $academicYear->id)
                        ->where('department_id', $department->id)
                        ->where('level', $level)
                        ->exists();
                    
                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // Déterminer les montants selon le niveau
                    $fees = $this->getFeesForLevel($level);
                    
                    Amount::create([
                        'uuid' => Str::uuid(),
                        'academic_year_id' => $academicYear->id,
                        'department_id' => $department->id,
                        'level' => $level,
                        'registration_fee' => $fees['registration'],
                        'national_training_fee' => $fees['national'],
                        'international_training_fee' => $fees['international'],
                        'exempted_training_fee' => $fees['exempted'],
                        'sponsored_training_fee' => $fees['sponsored'],
                    ]);
                    
                    $created++;
                    $this->command->line("      ✅ Niveau {$level} ({$levelName}): " . number_format($fees['registration'] + $fees['national'], 0, ',', ' ') . " FCFA (nationaux)");
                }
            }
            $this->command->newLine();
        }

        $this->command->newLine();
        $this->command->info("╔═══════════════════════════════════════════════════════════════╗");
        $this->command->info("║              RÉSUMÉ CRÉATION DES BARÈMES                      ║");
        $this->command->info("╚═══════════════════════════════════════════════════════════════╝");
        $this->command->info("✅ Barèmes créés: {$created}");
        $this->command->info("⏭️  Barèmes déjà existants: {$skipped}");
    }

    /**
     * Déterminer les niveaux à créer pour un département selon son cycle
     */
    private function getLevelsForDepartment(Department $department): array
    {
        $cycleName = $department->cycle?->name ?? '';

        // Ingénierie: Prépa (1) + Spécialité (2)
        if (stripos($cycleName, 'Ingé') !== false || stripos($cycleName, 'Ing') !== false) {
            return [
                1 => 'Prépa',
                2 => 'Spécialité',
            ];
        }

        // Licence: 3 niveaux
        if (stripos($cycleName, 'Licence') !== false) {
            return [
                1 => 'Licence 1',
                2 => 'Licence 2',
                3 => 'Licence 3',
            ];
        }

        // Master: 2 niveaux
        if (stripos($cycleName, 'Master') !== false) {
            return [
                1 => 'Master 1',
                2 => 'Master 2',
            ];
        }

        // Par défaut: 1 niveau
        return [1 => 'Niveau 1'];
    }

    /**
     * Obtenir les frais selon le niveau
     * 
     * RÈGLES:
     * - Niveau 1 (Prépa/Licence 1): 425 000 FCFA nationaux (25k inscription + 400k formation)
     * - Niveau 2+ (Spécialité/Master): 825 000 FCFA nationaux (25k inscription + 800k formation)
     * - Non-nationaux: +400 000 FCFA
     */
    private function getFeesForLevel(int $level): array
    {
        $registration = 25000; // Frais d'inscription fixes

        if ($level === 1) {
            // Niveau 1: Prépa / Licence 1
            return [
                'registration' => $registration,
                'national' => 400000,      // Total: 425 000 FCFA
                'international' => 800000,  // Total: 825 000 FCFA
                'exempted' => 0,
                'sponsored' => 200000,     // 50% de réduction (exemple)
            ];
        } else {
            // Niveau 2+: Spécialité / Master / Licence 2-3
            return [
                'registration' => $registration,
                'national' => 800000,       // Total: 825 000 FCFA
                'international' => 1000000, // Total: 1 025 000 FCFA
                'exempted' => 0,
                'sponsored' => 400000,      // 50% de réduction (exemple)
            ];
        }
    }
}

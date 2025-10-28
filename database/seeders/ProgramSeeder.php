<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Cours\Models\Program;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Support\Str;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->warn('⚠️  Aucun département trouvé. Exécutez DepartmentSeeder d\'abord.');
            return;
        }

        $programs = [];

        // Programmes Licence
        foreach ($departments->where('code', 'like', '%-L') as $dept) {
            for ($semester = 1; $semester <= 6; $semester++) {
                $programs[] = [
                    'name' => "{$dept->name} - Semestre {$semester}",
                    'code' => "{$dept->code}-S{$semester}",
                    'department_id' => $dept->id,
                    'academic_year_id' => $currentYear?->id,
                    'semester' => $semester,
                    'description' => "Programme du semestre {$semester} pour {$dept->name}",
                    'is_active' => true,
                ];
            }
        }

        // Programmes Master
        foreach ($departments->where('code', 'like', '%-M') as $dept) {
            for ($semester = 1; $semester <= 4; $semester++) {
                $programs[] = [
                    'name' => "{$dept->name} - Semestre {$semester}",
                    'code' => "{$dept->code}-S{$semester}",
                    'department_id' => $dept->id,
                    'academic_year_id' => $currentYear?->id,
                    'semester' => $semester,
                    'description' => "Programme du semestre {$semester} pour {$dept->name}",
                    'is_active' => true,
                ];
            }
        }

        // Programmes Ingénieur
        foreach ($departments->where('code', 'like', '%-I') as $dept) {
            for ($semester = 1; $semester <= 10; $semester++) {
                $programs[] = [
                    'name' => "{$dept->name} - Semestre {$semester}",
                    'code' => "{$dept->code}-S{$semester}",
                    'department_id' => $dept->id,
                    'academic_year_id' => $currentYear?->id,
                    'semester' => $semester,
                    'description' => "Programme du semestre {$semester} pour {$dept->name}",
                    'is_active' => true,
                ];
            }
        }

        foreach ($programs as $programData) {
            Program::updateOrCreate(
                ['code' => $programData['code']],
                array_merge($programData, ['uuid' => Str::uuid()->toString()])
            );
        }

        $this->command->info('✅ Programmes créés avec succès!');
        $this->command->info("📚 Total: " . count($programs) . " programmes créés");
    }
}

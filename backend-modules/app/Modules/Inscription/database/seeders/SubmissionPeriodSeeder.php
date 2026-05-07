<?php

namespace App\Modules\Inscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubmissionPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Récupérer les années académiques
        $academicYears = DB::table('academic_years')->get();
        
        // Récupérer tous les départements
        $departments = DB::table('departments')->get();
        
        foreach ($academicYears as $year) {
            foreach ($departments as $department) {
                // Créer une période de soumission pour chaque département et année
                DB::table('submission_periods')->updateOrInsert(
                    [
                        'academic_year_id' => $year->id,
                        'department_id' => $department->id,
                    ],
                    [
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'start_date' => Carbon::parse($year->submission_start),
                        'end_date' => Carbon::parse($year->submission_end),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
        
        echo "Submission periods seeded successfully!\n";
    }
}

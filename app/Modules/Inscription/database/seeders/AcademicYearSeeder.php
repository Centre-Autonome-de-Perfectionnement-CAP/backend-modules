<?php

namespace App\Modules\Inscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = [
            [
                'academic_year' => '2023-2024',
                'year_start' => '2023-09-01',
                'year_end' => '2024-08-31',
                'submission_start' => '2023-06-01',
                'submission_end' => '2023-10-31',
            ],
            [
                'academic_year' => '2024-2025',
                'year_start' => '2024-09-01',
                'year_end' => '2025-08-31',
                'submission_start' => '2024-06-01',
                'submission_end' => '2024-10-31',
            ],
            [
                'academic_year' => '2025-2026',
                'year_start' => '2025-09-01',
                'year_end' => '2026-08-31',
                'submission_start' => '2025-06-01',
                'submission_end' => '2026-12-31',
            ],
        ];

        foreach ($years as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['academic_year' => $year['academic_year']],
                [
                    'academic_year' => $year['academic_year'],
                    'year_start' => $year['year_start'],
                    'year_end' => $year['year_end'],
                    'submission_start' => $year['submission_start'],
                    'submission_end' => $year['submission_end'],
                    'uuid' => Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Academic years seeded successfully!');
    }
}

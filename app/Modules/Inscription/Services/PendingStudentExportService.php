<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Http\Request;

class PendingStudentExportService
{
    public function validateStudentsHavestatus(array $filters): ?array
    {
        $query = PendingStudent::query();

        if (!empty($filters['year']) && $filters['year'] !== 'all' && is_numeric($filters['year'])) {
            $query->where('academic_year_id', $filters['year']);
        }

        if (!empty($filters['filiere']) && $filters['filiere'] !== 'all' && is_numeric($filters['filiere'])) {
            $query->where('department_id', $filters['filiere']);
        }

        if (!empty($filters['cohort']) && $filters['cohort'] !== 'all' && !empty($filters['year']) && is_numeric($filters['year'])) {
            $periods = \DB::table('submission_periods')
                ->where('academic_year_id', $filters['year'])
                ->select('start_date', 'end_date')
                ->groupBy('start_date', 'end_date')
                ->orderBy('start_date')
                ->get();

            $cohortIndex = (int)$filters['cohort'] - 1;
            if (isset($periods[$cohortIndex])) {
                $period = $periods[$cohortIndex];
                $query->whereDate('created_at', '>=', $period->start_date)
                      ->whereDate('created_at', '<=', $period->end_date);
            }
        }

        $pendingCount = $query->where('status', 'pending')->count();

        // if ($pendingCount > 0) {
        //     return [
        //         'error' => true,
        //         'message' => "Impossible d'exporter la liste CUCA-CUO : {$pendingCount} étudiant(s) ont un status 'En attente'. Veuillez définir un avis CUCA (Admis/Refusé) pour tous les étudiants avant d'exporter."
        //     ];
        // }

        return null;
    }

    public function prepareExportData(array $filters): array
    {
        $query = PendingStudent::with(['personalInformation', 'department', 'academicYear']);

        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            if (is_numeric($filters['year'])) {
                $query->where('academic_year_id', $filters['year']);
            } else {
                $query->whereHas('academicYear', function($q) use ($filters) {
                    $q->where('academic_year', $filters['year']);
                });
            }
        }

        if (!empty($filters['filiere']) && $filters['filiere'] !== 'all') {
            if (is_numeric($filters['filiere'])) {
                $query->where('department_id', $filters['filiere']);
            } else {
                $query->whereHas('department', function($q) use ($filters) {
                    $q->where('name', $filters['filiere']);
                });
            }
        }

        if (!empty($filters['cohort']) && $filters['cohort'] !== 'all' && !empty($filters['year']) && is_numeric($filters['year'])) {
            $periods = \DB::table('submission_periods')
                ->where('academic_year_id', $filters['year'])
                ->select('start_date', 'end_date')
                ->groupBy('start_date', 'end_date')
                ->orderBy('start_date')
                ->get();

            $cohortIndex = (int)$filters['cohort'] - 1;
            if (isset($periods[$cohortIndex])) {
                $period = $periods[$cohortIndex];
                $query->whereDate('created_at', '>=', $period->start_date)
                      ->whereDate('created_at', '<=', $period->end_date);
            }
        }

        $pendingStudents = $query->get();

        $academicYear = null;
        if (!empty($filters['year']) && is_numeric($filters['year'])) {
            $academicYear = AcademicYear::find($filters['year']);
        } else {
            $academicYear = AcademicYear::where('is_current', true)->first();
        }

        $department = $pendingStudents->first()?->department;
        $isPrepa = $department && strpos(strtolower($department->name), 'prepa') !== false;

        return [
            'pendingStudents' => $pendingStudents,
            'academicYear' => $academicYear?->academic_year ?? 'N/A',
            'department' => $department?->name ?? 'Toutes filières',
            'formation' => $department?->name ?? 'Formation générale',
            'isPrepa' => $isPrepa,
            'includeContact' => false,
            'cohort' => $filters['cohort'] ?? 'all'
        ];
    }

    public function generateFilename(string $extension, array $data): string
    {
        $department = str_replace(' ', '_', $data['department']);
        $academicYear = str_replace(['/', '-'], '_', $data['academicYear']);
        $cohort = $data['cohort'] ?? 'all';
        $dateTime = now()->format('Ymd_His');

        return "LISTE_CUCA_CUO_{$academicYear}_{$department}_{$cohort}_{$dateTime}.{$extension}";
    }

    public function getTemplate(bool $isPrepa): string
    {
        return $isPrepa ? 'liste-cuca-cuo-prepa' : 'liste-cuca-cuo';
    }

    public function prepareEmailsExportData(array $filters): array
    {
        \Log::info('=== prepareEmailsExportData START ===');
        $query = PendingStudent::with(['personalInformation', 'department', 'academicYear']);

        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            if (is_numeric($filters['year'])) {
                $query->where('academic_year_id', $filters['year']);
                \Log::info('Filter by year:', ['year' => $filters['year']]);
            }
        }

        if (!empty($filters['filiere']) && $filters['filiere'] !== 'all') {
            if (is_numeric($filters['filiere'])) {
                $query->where('department_id', $filters['filiere']);
                \Log::info('Filter by filiere:', ['filiere' => $filters['filiere']]);
            }
        }

        if (!empty($filters['cohort']) && $filters['cohort'] !== 'all' && !empty($filters['year']) && is_numeric($filters['year'])) {
            $periods = \DB::table('submission_periods')
                ->where('academic_year_id', $filters['year'])
                ->select('start_date', 'end_date')
                ->groupBy('start_date', 'end_date')
                ->orderBy('start_date')
                ->get();

            $cohortIndex = (int)$filters['cohort'] - 1;
            if (isset($periods[$cohortIndex])) {
                $period = $periods[$cohortIndex];
                $query->whereDate('created_at', '>=', $period->start_date)
                      ->whereDate('created_at', '<=', $period->end_date);
                \Log::info('Filter by cohort:', ['cohort' => $filters['cohort']]);
            }
        }

        // Trier par département puis par nom
        $pendingStudents = $query->orderBy('department_id')
            ->get()
            ->sortBy(function($student) {
                return $student->personalInformation->last_name;
            });

        \Log::info('Students fetched:', ['count' => $pendingStudents->count()]);

        $academicYear = null;
        if (!empty($filters['year']) && is_numeric($filters['year'])) {
            $academicYear = AcademicYear::find($filters['year']);
        } else {
            $academicYear = AcademicYear::where('is_current', true)->first();
        }

        // Grouper par filière
        $studentsByDepartment = $pendingStudents->groupBy(function($student) {
            return $student->department->name ?? 'Sans filière';
        });

        \Log::info('Grouped by department:', ['departments' => $studentsByDepartment->keys()->toArray()]);

        $emails = $pendingStudents->map(function($student) {
            return [
                'name' => $student->personalInformation->last_name . ' ' . $student->personalInformation->first_names,
                'email' => $student->personalInformation->email,
                'department' => $student->department->name ?? 'N/A',
            ];
        });

        \Log::info('=== prepareEmailsExportData END ===');

        return [
            'emails' => $emails,
            'studentsByDepartment' => $studentsByDepartment,
            'academicYear' => $academicYear?->academic_year ?? 'N/A',
            'totalStudents' => $pendingStudents->count(),
            'exportDate' => now()->format('d/m/Y'),
        ];
    }

    public function generateEmailsFilename(array $data): string
    {
        $academicYear = str_replace(['/', '-'], '_', $data['academicYear']);
        $dateTime = now()->format('Ymd_His');

        return "EMAILS_ETUDIANTS_{$academicYear}_{$dateTime}.pdf";
    }

    /**
     * Prépare les données pour l'export des étudiants validés par type
     */
    public function prepareValidatedStudentsByTypeExportData(array $filters): array
    {
        $academicYearId = $filters['year'];
        $type = $filters['type']; // 'prepa', 'licence', 'specialite'
        
        $query = PendingStudent::with(['personalInformation', 'department.cycle', 'academicYear'])
            ->where('academic_year_id', $academicYearId);
        
        $students = collect();
        $typeLabel = '';
        $validationCriteria = '';
        
        switch ($type) {
            case 'prepa':
                $students = $query->get()->filter(function($student) {
                    $departmentName = strtolower($student->department->name ?? '');
                    $isPrepa = strpos($departmentName, 'prepa') !== false || strpos($departmentName, 'prépa') !== false;
                    return $isPrepa && $student->cuca_opinion === 'favorable';
                });
                $typeLabel = 'Classes Préparatoires';
                $validationCriteria = 'CUCA';
                break;
                
            case 'licence':
                $students = $query->get()->filter(function($student) {
                    $departmentName = strtolower($student->department->name ?? '');
                    $isPrepa = strpos($departmentName, 'prepa') !== false || strpos($departmentName, 'prépa') !== false;
                    $isSpecialite = strpos($departmentName, 'spécialité') !== false || strpos($departmentName, 'specialite') !== false;
                    return !$isPrepa && !$isSpecialite && $student->cuo_opinion === 'favorable';
                });
                $typeLabel = 'Licence / Master';
                $validationCriteria = 'CUO';
                break;
                
            case 'specialite':
                $students = $query->get()->filter(function($student) {
                    $departmentName = strtolower($student->department->name ?? '');
                    $isSpecialite = strpos($departmentName, 'spécialité') !== false || strpos($departmentName, 'specialite') !== false;
                    return $isSpecialite && $student->cuo_opinion === 'favorable';
                });
                $typeLabel = 'Première Année de Spécialité';
                $validationCriteria = 'CUO';
                break;
        }
        
        $students = $students->sortBy(function($student) {
            return $student->personalInformation->last_name;
        });
        
        $academicYear = AcademicYear::find($academicYearId);
        
        return [
            'students' => $students,
            'totalStudents' => $students->count(),
            'typeLabel' => $typeLabel,
            'validationCriteria' => $validationCriteria,
            'type' => $type,
            'academicYear' => $academicYear?->academic_year ?? 'N/A',
            'exportDate' => now()->format('d/m/Y'),
            'exportTime' => now()->format('H:i'),
        ];
    }
    
    /**
     * Génère le nom de fichier pour l'export des étudiants validés
     */
    public function generateValidatedStudentsFilename(array $data): string
    {
        $academicYear = str_replace(['/', '-'], '_', $data['academicYear']);
        $type = strtoupper($data['type']);
        $dateTime = now()->format('Ymd_His');
        
        return "ETUDIANTS_VALIDES_{$type}_{$academicYear}_{$dateTime}.pdf";
    }}

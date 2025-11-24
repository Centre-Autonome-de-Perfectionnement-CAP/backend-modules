<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\StudentGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClassGroupService
{
    /**
     * Récupérer les groupes d'une classe
     */
    public function getGroups($academicYearId, $departmentId, $studyLevel, $cohort = null)
    {
        $query = ClassGroup::with(['studentGroups.student'])
            ->where('academic_year_id', $academicYearId)
            ->where('department_id', $departmentId)
            ->where('study_level', $studyLevel);
        
        if ($cohort) {
            $query->whereHas('studentGroups', function($q) use ($cohort, $academicYearId) {
                $q->whereHas('student.studentPendingStudents.academicPaths', function($subQ) use ($cohort, $academicYearId) {
                    $subQ->where('cohort', $cohort)
                         ->where('academic_year_id', $academicYearId);
                });
            });
        }
        
        $query->orderBy('group_name');

        return $query->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'group_name' => $group->group_name,
                'students_count' => $group->studentGroups->count(),
                'students' => $group->studentGroups->map(function ($sg) {
                    return [
                        'id' => $sg->student->id,
                        'matricule' => $sg->student->matricule,
                        'nom_prenoms' => $sg->student->nom . ' ' . $sg->student->prenoms,
                    ];
                }),
            ];
        });
    }

    /**
     * Créer des groupes pour une classe
     */
    public function createGroups(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Supprimer les anciens groupes de cette cohorte si demandé
            if ($data['replace_existing'] ?? false) {
                $this->deleteGroupsByCohort(
                    $data['academic_year_id'],
                    $data['department_id'],
                    $data['study_level'],
                    $data['cohort'] ?? null
                );
            }

            $createdGroups = [];

            // Créer chaque groupe avec ses étudiants
            foreach ($data['groups'] as $groupData) {
                $classGroup = ClassGroup::create([
                    'uuid' => (string) Str::uuid(),
                    'academic_year_id' => $data['academic_year_id'],
                    'department_id' => $data['department_id'],
                    'study_level' => $data['study_level'],
                    'group_name' => $groupData['name'],
                ]);

                // Ajouter les étudiants au groupe
                foreach ($groupData['student_ids'] as $studentId) {
                    StudentGroup::create([
                        'uuid' => (string) Str::uuid(),
                        'class_group_id' => $classGroup->id,
                        'student_id' => $studentId,
                    ]);
                }

                $createdGroups[] = [
                    'id' => $classGroup->id,
                    'group_name' => $classGroup->group_name,
                    'students_count' => count($groupData['student_ids']),
                ];
            }

            Log::info('Groupes de classe créés', [
                'academic_year_id' => $data['academic_year_id'],
                'department_id' => $data['department_id'],
                'study_level' => $data['study_level'],
                'groups_count' => count($createdGroups),
            ]);

            return $createdGroups;
        });
    }

    /**
     * Récupérer les détails d'un groupe
     */
    public function getGroupDetails(ClassGroup $classGroup)
    {
        $classGroup->load(['studentGroups.student', 'academicYear', 'department']);

        return [
            'id' => $classGroup->id,
            'group_name' => $classGroup->group_name,
            'academic_year' => $classGroup->academicYear->academic_year,
            'department' => $classGroup->department->name,
            'study_level' => $classGroup->study_level,
            'students_count' => $classGroup->studentGroups->count(),
            'students' => $classGroup->studentGroups->map(function ($sg) {
                return [
                    'id' => $sg->student->id,
                    'matricule' => $sg->student->matricule,
                    'nom_prenoms' => $sg->student->nom . ' ' . $sg->student->prenoms,
                    'sexe' => $sg->student->sexe,
                ];
            }),
        ];
    }

    /**
     * Supprimer un groupe
     */
    public function deleteGroup(ClassGroup $classGroup): bool
    {
        try {
            $classGroup->delete();

            Log::info('Groupe de classe supprimé', [
                'class_group_id' => $classGroup->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur suppression groupe de classe', [
                'class_group_id' => $classGroup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Supprimer tous les groupes d'une classe
     */
    public function deleteAllGroups($academicYearId, $departmentId, $studyLevel): int
    {
        return DB::transaction(function () use ($academicYearId, $departmentId, $studyLevel) {
            $deleted = ClassGroup::where('academic_year_id', $academicYearId)
                ->where('department_id', $departmentId)
                ->where('study_level', $studyLevel)
                ->delete();

            Log::info('Tous les groupes de classe supprimés', [
                'academic_year_id' => $academicYearId,
                'department_id' => $departmentId,
                'study_level' => $studyLevel,
                'deleted_count' => $deleted,
            ]);

            return $deleted;
        });
    }
    
    /**
     * Supprimer les groupes d'une cohorte spécifique
     */
    public function deleteGroupsByCohort($academicYearId, $departmentId, $studyLevel, $cohort): int
    {
        return DB::transaction(function () use ($academicYearId, $departmentId, $studyLevel, $cohort) {
            $groupIds = ClassGroup::where('academic_year_id', $academicYearId)
                ->where('department_id', $departmentId)
                ->where('study_level', $studyLevel)
                ->whereHas('studentGroups', function($q) use ($cohort, $academicYearId) {
                    $q->whereHas('student.studentPendingStudents.academicPaths', function($subQ) use ($cohort, $academicYearId) {
                        $subQ->where('cohort', $cohort)
                             ->where('academic_year_id', $academicYearId);
                    });
                })
                ->pluck('id');
            
            $deleted = ClassGroup::whereIn('id', $groupIds)->delete();

            Log::info('Groupes de cohorte supprimés', [
                'academic_year_id' => $academicYearId,
                'department_id' => $departmentId,
                'study_level' => $studyLevel,
                'cohort' => $cohort,
                'deleted_count' => $deleted,
            ]);

            return $deleted;
        });
    }
    
    /**
     * Créer un groupe unique par défaut avec tous les étudiants d'une cohorte
     */
    public function createDefaultGroup($academicYearId, $departmentId, $studyLevel, $cohort)
    {
        return DB::transaction(function () use ($academicYearId, $departmentId, $studyLevel, $cohort) {
            // Récupérer tous les étudiants de cette cohorte
            $students = DB::table('students')
                ->join('student_pending_student', 'students.id', '=', 'student_pending_student.student_id')
                ->join('academic_paths', 'student_pending_student.id', '=', 'academic_paths.student_pending_student_id')
                ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
                ->where('academic_paths.academic_year_id', $academicYearId)
                ->where('academic_paths.cohort', $cohort)
                ->where('academic_paths.study_level', $studyLevel)
                ->where('pending_students.department_id', $departmentId)
                ->select('students.id')
                ->get();
            
            if ($students->isEmpty()) {
                return null;
            }
            
            // Créer le groupe unique "A"
            $classGroup = ClassGroup::create([
                'uuid' => (string) Str::uuid(),
                'academic_year_id' => $academicYearId,
                'department_id' => $departmentId,
                'study_level' => $studyLevel,
                'group_name' => 'A',
            ]);
            
            // Ajouter tous les étudiants au groupe
            foreach ($students as $student) {
                StudentGroup::create([
                    'uuid' => (string) Str::uuid(),
                    'class_group_id' => $classGroup->id,
                    'student_id' => $student->id,
                ]);
            }
            
            Log::info('Groupe unique par défaut créé', [
                'academic_year_id' => $academicYearId,
                'department_id' => $departmentId,
                'study_level' => $studyLevel,
                'cohort' => $cohort,
                'students_count' => $students->count(),
            ]);
            
            return [
                'id' => $classGroup->id,
                'group_name' => $classGroup->group_name,
                'students_count' => $students->count(),
            ];
        });
    }
    
    /**
     * Récupérer les groupes d'une classe par ID
     */
    public function getGroupsByClassId(int $classGroupId)
    {
        $classGroup = ClassGroup::with(['academicYear', 'department'])->findOrFail($classGroupId);
        
        $academicYear = \App\Modules\Inscription\Models\AcademicYear::where('is_current', true)
            ->orWhere('year_start', '>=', now())
            ->orderBy('year_start', 'asc')
            ->first() ?? $classGroup->academicYear;
        
        return $this->getGroups(
            $academicYear->id,
            $classGroup->department_id,
            $classGroup->study_level
        );
    }
}

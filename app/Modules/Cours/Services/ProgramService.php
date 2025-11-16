<?php

namespace App\Modules\Cours\Services;

use App\Modules\Cours\Models\Program;
use Illuminate\Support\Facades\Log;
use Exception;

class ProgramService
{
    /**
     * Récupérer tous les programmes avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Program::query()
            ->with([
                'classGroup',
                'academicYear',
                'courseElementProfessor.courseElement.teachingUnit',
                'courseElementProfessor.professor'
            ]);

        if (!empty($filters['class_group_id'])) {
            $query->where('class_group_id', $filters['class_group_id']);
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

<<<<<<< HEAD
=======
        // Filtre par année académique
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        // Filtre par élément de cours
>>>>>>> f355611 (draft)
        if (!empty($filters['course_element_id'])) {
            $query->whereHas('courseElementProfessor', function ($q) use ($filters) {
                $q->where('course_element_id', $filters['course_element_id']);
            });
        }

        if (!empty($filters['professor_id'])) {
            $query->whereHas('courseElementProfessor', function ($q) use ($filters) {
                $q->where('professor_id', $filters['professor_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('courseElementProfessor.courseElement', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhereHas('courseElementProfessor.professor', function ($subQ) use ($search) {
                    $subQ->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('classGroup', function ($subQ) use ($search) {
                    $subQ->where('study_level', 'like', "%{$search}%")
                         ->orWhere('group_name', 'like', "%{$search}%")
                         ->orWhereHas('department', function ($deptQ) use ($search) {
                             $deptQ->where('name', 'like', "%{$search}%");
                         });
                });
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un nouveau programme
     */
    public function create(array $data): Program
    {
        $exists = Program::where('class_group_id', $data['class_group_id'])
            ->where('course_element_professor_id', $data['course_element_professor_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists();

        if ($exists) {
            throw new Exception('Ce programme existe déjà pour cette année académique');
        }

        $program = Program::create($data);

        Log::info('Programme créé', [
            'program_id' => $program->id,
            'class_group_id' => $program->class_group_id,
            'course_element_professor_id' => $program->course_element_professor_id,
            'academic_year_id' => $program->academic_year_id,
        ]);

        return $program->load([
            'classGroup',
            'academicYear',
            'courseElementProfessor.courseElement.teachingUnit',
            'courseElementProfessor.professor'
        ]);
    }

    /**
     * Récupérer un programme par ID
     */
    public function getById(int $id): ?Program
    {
        return Program::with([
            'classGroup',
            'courseElementProfessor.courseElement.teachingUnit',
            'courseElementProfessor.professor'
        ])->find($id);
    }

    /**
     * Mettre à jour un programme
     */
    public function update(Program $program, array $data): Program
    {
        $program->update($data);

        Log::info('Programme mis à jour', [
            'program_id' => $program->id,
        ]);

        return $program->fresh([
            'classGroup',
            'courseElementProfessor.courseElement.teachingUnit',
            'courseElementProfessor.professor'
        ]);
    }

    /**
     * Supprimer un programme
     */
    public function delete(Program $program): bool
    {
        try {
            $program->delete();

            Log::info('Programme supprimé', [
                'program_id' => $program->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression du programme', [
                'program_id' => $program->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Récupérer les programmes d'un groupe de classe (emploi du temps)
     */
    public function getProgramsByClassGroup(int $classGroupId, int $perPage = 50)
    {
        return Program::with([
            'academicYear',
            'courseElementProfessor.courseElement.teachingUnit',
            'courseElementProfessor.professor'
        ])
            ->where('class_group_id', $classGroupId)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Récupérer les programmes d'un professeur
     */
    public function getProgramsByProfessor(int $professorId, int $perPage = 50)
    {
        return Program::with([
            'classGroup',
            'academicYear',
            'courseElementProfessor.courseElement.teachingUnit'
        ])
            ->whereHas('courseElementProfessor', function ($q) use ($professorId) {
                $q->where('professor_id', $professorId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Récupérer les programmes d'un élément de cours
     */
    public function getProgramsByCourseElement(int $courseElementId, int $perPage = 50)
    {
        return Program::with([
            'classGroup',
            'academicYear',
            'courseElementProfessor.professor'
        ])
            ->whereHas('courseElementProfessor', function ($q) use ($courseElementId) {
                $q->where('course_element_id', $courseElementId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Créer plusieurs programmes en masse
     */
    public function bulkCreate(array $programsData): array
    {
        $createdPrograms = [];
        $errors = [];

        foreach ($programsData as $index => $data) {
            try {
                // Vérifier si cette combinaison existe déjà
                $exists = Program::where('class_group_id', $data['class_group_id'])
                    ->where('course_element_professor_id', $data['course_element_professor_id'])
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->exists();

                if ($exists) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $data,
                        'error' => 'Ce cours est déjà assigné à ce groupe de classe pour cette année.',
                    ];
                    continue;
                }

                $program = Program::create($data);
                $createdPrograms[] = $program->load([
                    'classGroup',
                    'academicYear',
                    'courseElementProfessor.courseElement.teachingUnit',
                    'courseElementProfessor.professor'
                ]);

                Log::info('Programme créé (bulk)', [
                    'program_id' => $program->id,
                    'class_group_id' => $program->class_group_id,
                ]);
            } catch (Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'data' => $data,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $createdPrograms,
            'errors' => $errors,
            'success_count' => count($createdPrograms),
            'error_count' => count($errors),
        ];
    }

    /**
     * Copier les programmes d'une classe à une autre
     * Utile pour dupliquer un emploi du temps d'une année à une autre
     */
    public function copyPrograms(int $sourceClassGroupId, int $targetClassGroupId): array
    {
        $sourcePrograms = Program::where('class_group_id', $sourceClassGroupId)->get();

        if ($sourcePrograms->isEmpty()) {
            return [
                'created' => [],
                'errors' => [],
                'success_count' => 0,
                'error_count' => 0,
                'message' => 'Aucun programme trouvé dans la classe source.',
            ];
        }

        $createdPrograms = [];
        $errors = [];
        $skipped = [];

        foreach ($sourcePrograms as $sourceProgram) {
            try {
                // Vérifier si cette assignation existe déjà pour la classe cible
                $exists = Program::where('class_group_id', $targetClassGroupId)
                    ->where('course_element_professor_id', $sourceProgram->course_element_professor_id)
                    ->where('academic_year_id', $sourceProgram->academic_year_id)
                    ->exists();

                if ($exists) {
                    $skipped[] = [
                        'source_program_id' => $sourceProgram->id,
                        'course_element_professor_id' => $sourceProgram->course_element_professor_id,
                        'reason' => 'Ce cours existe déjà dans la classe cible.',
                    ];
                    continue;
                }

                // Créer le nouveau programme avec les mêmes informations
                $newProgram = Program::create([
                    'class_group_id' => $targetClassGroupId,
                    'course_element_professor_id' => $sourceProgram->course_element_professor_id,
                    'academic_year_id' => $sourceProgram->academic_year_id,
                    'weighting' => $sourceProgram->weighting,
                    'retake_weighting' => $sourceProgram->retake_weighting,
                ]);

                $createdPrograms[] = $newProgram->load([
                    'classGroup',
                    'academicYear',
                    'courseElementProfessor.courseElement.teachingUnit',
                    'courseElementProfessor.professor'
                ]);

                Log::info('Programme copié', [
                    'source_program_id' => $sourceProgram->id,
                    'new_program_id' => $newProgram->id,
                    'source_class_group_id' => $sourceClassGroupId,
                    'target_class_group_id' => $targetClassGroupId,
                ]);
            } catch (Exception $e) {
                $errors[] = [
                    'source_program_id' => $sourceProgram->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $createdPrograms,
            'skipped' => $skipped,
            'errors' => $errors,
            'success_count' => count($createdPrograms),
            'skipped_count' => count($skipped),
            'error_count' => count($errors),
            'total_source' => $sourcePrograms->count(),
        ];
    }

    public function renewForNextYear(int $currentAcademicYearId, int $nextAcademicYearId): array
    {
        $programs = Program::where('academic_year_id', $currentAcademicYearId)->get();
        $created = [];
        $errors = [];

        foreach ($programs as $program) {
            try {
                $exists = Program::where('class_group_id', $program->class_group_id)
                    ->where('course_element_professor_id', $program->course_element_professor_id)
                    ->where('academic_year_id', $nextAcademicYearId)
                    ->exists();

                if (!$exists) {
                    $created[] = Program::create([
                        'class_group_id' => $program->class_group_id,
                        'course_element_professor_id' => $program->course_element_professor_id,
                        'academic_year_id' => $nextAcademicYearId,
                        'weighting' => $program->weighting,
                        'retake_weighting' => $program->retake_weighting,
                    ]);
                }
            } catch (Exception $e) {
                $errors[] = ['program_id' => $program->id, 'error' => $e->getMessage()];
            }
        }

        return ['created' => count($created), 'errors' => $errors];
    }
}

<?php

namespace App\Modules\Cours\Services;

use App\Modules\Cours\Models\CourseElementProfessor;
use Illuminate\Support\Facades\Log;
use Exception;

class CourseElementProfessorService
{
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = CourseElementProfessor::query()
            ->with(['courseElement.teachingUnit', 'professor', 'academicYear', 'classGroup']);

        if (!empty($filters['course_element_id'])) {
            $query->where('course_element_id', $filters['course_element_id']);
        }

        if (!empty($filters['professor_id'])) {
            $query->where('professor_id', $filters['professor_id']);
        }

        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (!empty($filters['class_group_id'])) {
            $query->where('class_group_id', $filters['class_group_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('courseElement', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })->orWhereHas('professor', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): CourseElementProfessor
    {
        $existing = CourseElementProfessor::where('course_element_id', $data['course_element_id'])
            ->where('professor_id', $data['professor_id'])
            ->first();

        if ($existing) {
            throw new Exception('Cette association existe déjà');
        }

        $assignment = CourseElementProfessor::create($data);

        Log::info('Association Matière-Professeur créée', [
            'id' => $assignment->id,
            'course_element_id' => $assignment->course_element_id,
            'professor_id' => $assignment->professor_id,
            'is_primary' => $assignment->is_primary ?? false,
        ]);

        return $assignment->load(['courseElement.teachingUnit', 'professor']);
    }

    public function update(CourseElementProfessor $assignment, array $data): CourseElementProfessor
    {
        $assignment->update($data);

        Log::info('Association Matière-Professeur mise à jour', [
            'id' => $assignment->id,
        ]);

        return $assignment->fresh(['courseElement.teachingUnit', 'professor', 'academicYear', 'classGroup']);
    }

    public function delete(CourseElementProfessor $assignment): bool
    {
        try {
            $assignment->delete();

            Log::info('Association Matière-Professeur supprimée', [
                'id' => $assignment->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'association', [
                'id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getByCourseElement(int $courseElementId, ?int $academicYearId = null, ?int $classGroupId = null)
    {
        $query = CourseElementProfessor::where('course_element_id', $courseElementId)
            ->with(['professor', 'courseElement', 'academicYear', 'classGroup']);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        }

        return $query->get();
    }

    public function renewForNextYear(int $currentAcademicYearId, int $nextAcademicYearId): array
    {
        $assignments = CourseElementProfessor::where('academic_year_id', $currentAcademicYearId)->get();
        $created = [];
        $errors = [];

        foreach ($assignments as $assignment) {
            try {
                $exists = CourseElementProfessor::where('course_element_id', $assignment->course_element_id)
                    ->where('professor_id', $assignment->professor_id)
                    ->where('academic_year_id', $nextAcademicYearId)
                    ->where('class_group_id', $assignment->class_group_id)
                    ->exists();

                if (!$exists) {
                    $created[] = CourseElementProfessor::create([
                        'course_element_id' => $assignment->course_element_id,
                        'professor_id' => $assignment->professor_id,
                        'academic_year_id' => $nextAcademicYearId,
                        'class_group_id' => $assignment->class_group_id,
                        'is_primary' => $assignment->is_primary,
                    ]);
                }
            } catch (Exception $e) {
                $errors[] = ['assignment_id' => $assignment->id, 'error' => $e->getMessage()];
            }
        }

        return ['created' => count($created), 'errors' => $errors];
    }
}

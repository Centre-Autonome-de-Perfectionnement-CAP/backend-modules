<?php

namespace App\Modules\Cours\Services;

use App\Modules\Cours\Models\CourseElement;
use Illuminate\Support\Facades\Log;
use Exception;

class CourseElementService
{
    /**
     * Récupérer tous les éléments de cours avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = CourseElement::query()->with(['teachingUnit', 'resources']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['teaching_unit_id'])) {
            $query->where('teaching_unit_id', $filters['teaching_unit_id']);
        }

        if (!empty($filters['credits'])) {
            $query->where('credits', $filters['credits']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un nouvel élément de cours
     */
    public function create(array $data): CourseElement
    {
        $courseElement = CourseElement::create($data);

        Log::info('Élément de cours créé', [
            'course_element_id' => $courseElement->id,
            'code' => $courseElement->code,
        ]);

        return $courseElement;
    }

    /**
     * Récupérer un élément de cours par ID
     */
    public function getById(int $id): ?CourseElement
    {
        return CourseElement::with(['teachingUnit', 'resources.file'])->find($id);
    }

    /**
     * Mettre à jour un élément de cours
     */
    public function update(CourseElement $courseElement, array $data): CourseElement
    {
        $courseElement->update($data);

        Log::info('Élément de cours mis à jour', [
            'course_element_id' => $courseElement->id,
        ]);

        return $courseElement->fresh(['teachingUnit']);
    }

    /**
     * Supprimer un élément de cours
     */
    public function delete(CourseElement $courseElement): bool
    {
        try {
            $courseElement->delete();

            Log::info('Élément de cours supprimé', [
                'course_element_id' => $courseElement->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'élément de cours', [
                'course_element_id' => $courseElement->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attacher un professeur à un élément de cours
     */
    public function attachProfessor(CourseElement $courseElement, int $professorId): void
    {
        $courseElement->professors()->syncWithoutDetaching([$professorId]);

        Log::info('Professeur attaché à l\'élément de cours', [
            'course_element_id' => $courseElement->id,
            'professor_id' => $professorId,
        ]);
    }

    /**
     * Détacher un professeur d'un élément de cours
     */
    public function detachProfessor(CourseElement $courseElement, int $professorId): void
    {
        $courseElement->professors()->detach($professorId);

        Log::info('Professeur détaché de l\'élément de cours', [
            'course_element_id' => $courseElement->id,
            'professor_id' => $professorId,
        ]);
    }
}

<?php

namespace App\Modules\CahierTexte\Services;

use App\Modules\CahierTexte\Models\TextbookEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class TextbookEntryService
{
    /**
     * Récupérer toutes les entrées avec filtres
     */
    public function getAll(array $filters = [])
    {
        $query = TextbookEntry::query()
            ->with([
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor',
                'program.classGroup',
                'scheduledCourse',
            ])
            ->withCount('comments');

        // Filtres
        if (!empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('session_title', 'like', "%{$filters['search']}%")
                  ->orWhere('content_covered', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['program_id'])) {
            $query->byProgram($filters['program_id']);
        }

        if (!empty($filters['class_group_id'])) {
            $query->byClassGroup($filters['class_group_id']);
        }

        if (!empty($filters['professor_id'])) {
            $query->byProfessor($filters['professor_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        // Tri
        $query->orderBy('session_date', 'desc')
              ->orderBy('start_time', 'desc');

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Récupérer une entrée par ID
     */
    public function getById(int $id)
    {
        return TextbookEntry::with([
            'program.courseElementProfessor.courseElement',
            'program.courseElementProfessor.professor',
            'program.classGroup',
            'scheduledCourse',
            'validator',
            'comments.user',
            'comments.replies.user',
        ])->findOrFail($id);
    }

    /**
     * Créer une nouvelle entrée
     */
    public function create(array $data)
    {
        try {
            $entry = TextbookEntry::create($data);
            
            Log::info('Entrée cahier de texte créée', [
                'entry_id' => $entry->id,
                'program_id' => $entry->program_id,
                'session_date' => $entry->session_date,
            ]);

            return $entry->load([
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor',
                'program.classGroup',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création entrée cahier de texte', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour une entrée
     */
    public function update(int $id, array $data)
    {
        try {
            $entry = TextbookEntry::findOrFail($id);
            $entry->update($data);
            
            Log::info('Entrée cahier de texte mise à jour', [
                'entry_id' => $entry->id,
            ]);

            return $entry->load([
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor',
                'program.classGroup',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour entrée cahier de texte', [
                'entry_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Supprimer une entrée
     */
    public function delete(int $id)
    {
        try {
            $entry = TextbookEntry::findOrFail($id);
            $entry->delete();
            
            Log::info('Entrée cahier de texte supprimée', [
                'entry_id' => $id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur suppression entrée cahier de texte', [
                'entry_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Publier une entrée
     */
    public function publish(int $id)
    {
        try {
            $entry = TextbookEntry::findOrFail($id);
            $entry->publish();
            
            Log::info('Entrée cahier de texte publiée', [
                'entry_id' => $id,
            ]);

            return $entry->fresh();
        } catch (\Exception $e) {
            Log::error('Erreur publication entrée cahier de texte', [
                'entry_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Valider une entrée
     */
    public function validate(int $id, $validator)
    {
        try {
            $entry = TextbookEntry::findOrFail($id);
            $entry->validate($validator);
            
            Log::info('Entrée cahier de texte validée', [
                'entry_id' => $id,
                'validator_id' => $validator->id,
            ]);

            return $entry->fresh(['validator']);
        } catch (\Exception $e) {
            Log::error('Erreur validation entrée cahier de texte', [
                'entry_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer les entrées par groupe de classe
     */
    public function getByClassGroup(int $classGroupId, array $filters = [])
    {
        $query = TextbookEntry::query()
            ->byClassGroup($classGroupId)
            ->with([
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor',
            ])
            ->withCount('comments');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        $query->orderBy('session_date', 'desc')
              ->orderBy('start_time', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Récupérer les entrées par professeur
     */
    public function getByProfessor(int $professorId, array $filters = [])
    {
        $query = TextbookEntry::query()
            ->byProfessor($professorId)
            ->with([
                'program.courseElementProfessor.courseElement',
                'program.classGroup',
            ])
            ->withCount('comments');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        $query->orderBy('session_date', 'desc')
              ->orderBy('start_time', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Récupérer les statistiques
     */
    public function getStatistics(array $filters = [])
    {
        $query = TextbookEntry::query();

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (!empty($filters['class_group_id'])) {
            $query->byClassGroup($filters['class_group_id']);
        }

        if (!empty($filters['professor_id'])) {
            $query->byProfessor($filters['professor_id']);
        }

        return [
            'total_entries' => $query->count(),
            'draft_entries' => (clone $query)->byStatus('draft')->count(),
            'published_entries' => (clone $query)->byStatus('published')->count(),
            'validated_entries' => (clone $query)->byStatus('validated')->count(),
            'total_hours_taught' => (clone $query)->sum('hours_taught'),
            'entries_with_homework' => (clone $query)->whereNotNull('homework')->count(),
        ];
    }
}

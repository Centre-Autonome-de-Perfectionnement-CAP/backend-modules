<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cours\Models\Program;
use App\Modules\CahierTexte\Models\TextbookEntry;
use App\Modules\CahierTexte\Http\Resources\TextbookEntryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TextbookProfessorController extends Controller
{
    /**
     * Récupère les statistiques globales du professeur connecté
     */
    public function stats(Request $request)
    {
        try {
            $professorId = Auth::id();
            
            // Récupérer tous les programmes du professeur
            $programIds = Program::whereHas('courseElementProfessor', function ($query) use ($professorId) {
                $query->where('professor_id', $professorId);
            })->pluck('id');
            
            // Statistiques des entrées
            $stats = TextbookEntry::whereIn('program_id', $programIds)
                ->select(
                    DB::raw('SUM(CASE WHEN status = "published" THEN hours_taught ELSE 0 END) as total_hours_published'),
                    DB::raw('SUM(CASE WHEN status = "draft" THEN hours_taught ELSE 0 END) as total_hours_draft'),
                    DB::raw('COUNT(CASE WHEN status = "published" THEN 1 END) as count_published'),
                    DB::raw('COUNT(CASE WHEN status = "draft" THEN 1 END) as count_draft')
                )
                ->first();
            
            // Nombre de programmes uniques
            $programsCount = $programIds->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_hours_published' => (float)($stats->total_hours_published ?? 0),
                    'total_hours_draft' => (float)($stats->total_hours_draft ?? 0),
                    'count_published' => (int)($stats->count_published ?? 0),
                    'count_draft' => (int)($stats->count_draft ?? 0),
                    'programs_count' => $programsCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Récupère la liste des programmes du professeur avec les compteurs d'entrées
     */
    public function programs(Request $request)
    {
        try {
            $professorId = Auth::id();
            
            // Récupérer tous les programmes du professeur avec les relations
            $programs = Program::whereHas('courseElementProfessor', function ($query) use ($professorId) {
                $query->where('professor_id', $professorId);
            })
            ->with([
                'courseElementProfessor.courseElement.teachingUnit',
                'classGroup.department',
                'academicYear'
            ])
            ->get();
            
            $result = $programs->map(function ($program) {
                // Compter les entrées par statut
                $entriesPublished = TextbookEntry::where('program_id', $program->id)
                    ->where('status', 'published')
                    ->count();
                    
                $entriesDraft = TextbookEntry::where('program_id', $program->id)
                    ->where('status', 'draft')
                    ->count();
                
                return [
                    'id' => $program->id,
                    'uuid' => $program->uuid,
                    'course_name' => $program->course_name,
                    'course_code' => $program->course_code,
                    'class_name' => $program->classGroup->name ?? 'N/A',
                    'department_name' => $program->classGroup->department->name ?? 'N/A',
                    'academic_year' => $program->academicYear->academic_year ?? $program->academicYear->name ?? 'N/A',
                    'semester' => $program->semester,
                    'entries_published' => $entriesPublished,
                    'entries_draft' => $entriesDraft,
                    'entries_total' => $entriesPublished + $entriesDraft,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des programmes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Récupère les entrées du cahier de texte pour un programme spécifique
     */
    public function entries(Request $request, $programId)
    {
        try {
            $professorId = Auth::id();
            
            // Vérifier que le programme appartient bien au professeur
            $program = Program::where('id', $programId)
                ->whereHas('courseElementProfessor', function ($query) use ($professorId) {
                    $query->where('professor_id', $professorId);
                })
                ->first();
            
            if (!$program) {
                return response()->json([
                    'success' => false,
                    'message' => 'Programme non trouvé ou non autorisé',
                ], 404);
            }
            
            // Récupérer les entrées
            $entries = TextbookEntry::where('program_id', $programId)
                ->orderBy('session_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();
            
            // Calculer les statistiques du programme
            $stats = [
                'total_hours' => (float)$entries->sum('hours_taught'),
                'hours_published' => (float)$entries->where('status', 'published')->sum('hours_taught'),
                'hours_draft' => (float)$entries->where('status', 'draft')->sum('hours_taught'),
                'count_published' => $entries->where('status', 'published')->count(),
                'count_draft' => $entries->where('status', 'draft')->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $entries,
                    'stats' => $stats,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Publie une entrée (change le statut de draft à published)
     */
    public function publish(Request $request, $entryId)
    {
        try {
            $professorId = Auth::id();
            
            $entry = TextbookEntry::findOrFail($entryId);
            
            // Vérifier que l'entrée appartient bien au professeur
            $program = Program::findOrFail($entry->program_id);
            $hasAccess = Program::where('id', $program->id)
                ->whereHas('courseElementProfessor', function ($query) use ($professorId) {
                    $query->where('professor_id', $professorId);
                })
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé',
                ], 403);
            }
            
            // Vérifier que l'entrée est en brouillon
            if ($entry->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les entrées en brouillon peuvent être publiées',
                ], 422);
            }
            
            // Publier l'entrée
            $entry->status = 'published';
            $entry->published_at = now();
            $entry->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Entrée publiée avec succès',
                'data' => $entry->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Dépublie une entrée (retourne au statut draft)
     */
    public function unpublish(Request $request, $entryId)
    {
        try {
            $professorId = Auth::id();
            
            $entry = TextbookEntry::findOrFail($entryId);
            
            // Vérifier que l'entrée appartient bien au professeur
            $program = Program::findOrFail($entry->program_id);
            $hasAccess = Program::where('id', $program->id)
                ->whereHas('courseElementProfessor', function ($query) use ($professorId) {
                    $query->where('professor_id', $professorId);
                })
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé',
                ], 403);
            }
            
            // Vérifier que l'entrée est publiée
            if ($entry->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les entrées publiées peuvent être dépubliées',
                ], 422);
            }
            
            // Dépublier l'entrée
            $entry->status = 'draft';
            $entry->published_at = null;
            $entry->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Entrée dépubliée avec succès',
                'data' => $entry->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dépublication',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
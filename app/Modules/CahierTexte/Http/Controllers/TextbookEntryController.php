<?php

namespace App\Modules\CahierTexte\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CahierTexte\Http\Requests\CreateTextbookEntryRequest;
use App\Modules\CahierTexte\Http\Requests\UpdateTextbookEntryRequest;
use App\Modules\CahierTexte\Http\Resources\TextbookEntryResource;
use App\Modules\CahierTexte\Services\TextbookEntryService;
use Illuminate\Http\Request;
use App\Modules\CahierTexte\Models\TextbookEntry;

class TextbookEntryController extends Controller{
    protected $textbookEntryService;

    public function __construct(TextbookEntryService $textbookEntryService)  {
        $this->textbookEntryService = $textbookEntryService;
    }

 

    
    public function index(Request $request) {
        $perPage = $request->integer('per_page', 15);
        try {
    
            $query = TextbookEntry::with([
                'program.classGroup.department.cycle',  // Charger les relations imbriquées
                'program.courseElementProfessor.courseElement',
                'program.courseElementProfessor.professor',
            ]);

            if ($request->filled('program_id')) {
                $query->where('program_id', $request->program_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('session_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $entries = $query
                ->orderByDesc('session_date')
                ->paginate($perPage);

            $data = $entries->getCollection()->map(function ($entry) {
                $program = $entry->program;
                $cep = $program?->courseElementProfessor;
                $course = $cep?->courseElement;
                $professor = $cep?->professor;
                $classGroup = $program?->classGroup;
                
                // Récupérer le département et le cycle pour formater la classe
                $department = $classGroup?->department;
                $cycle = $department?->cycle;
                
                // Formater le nom de la classe: Department.name - Cycle.years_count - ClassGroup.group_name
                $formattedClassName = null;
                if ($classGroup) {
                    $departmentName = $department?->name ?? '';
                    $cycleYears = $classGroup->study_level ?? '';
                    $groupName = $classGroup->group_name ?? '';
                    $abs_dep = $department?->abbreviation ?? '';
                    $abs = $abs_dep." ". $cycleYears ." ". $groupName;
                    
                    $parts = array_filter([$departmentName, $cycleYears, $groupName]);
                    $formattedClassName = !empty($parts) ? implode(' - ', $parts) : $groupName;

                    $formattedClassName = $formattedClassName . " (". $abs .")";
                }

                return [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,

                    'course_element' => $course ? [
                        'id' => $course->id,
                        'name' => $course->name,
                        'code' => $course->code,
                    ] : null,

                    'professor' => $professor ? [
                        'id' => $professor->id,
                        'first_name' => $professor->first_name ?? '',
                        'last_name' => $professor->last_name ?? '',
                        'full_name' => trim(
                            ($professor->first_name ?? '') . ' ' .
                            ($professor->last_name ?? '')
                        ),
                        'email' => $professor->email ?? '',
                        'phone' => $professor->phone ?? '',
                        'rib_number' => $professor->rib_number ?? '',
                        'hourly_rate' => $professor->hourly_rate ?? 0,
                    ] : null,

                    'class_group' => $classGroup ? [
                        'id' => $classGroup->id,
                        'group_name' => $formattedClassName,  // Utiliser le nom formaté
                        'original_name' => $classGroup->group_name,  // Optionnel: garder le nom original
                    ] : null,

                    'program' => $program ? [
                        'id' => $program->id,
                        'semester' => $program->semester,
                        'quota_hours' => $program->quota_hours ?? $program->total_hours ?? 0,
                    ] : null,

                    // Entry fields
                    'session_date' => $entry->session_date?->format('Y-m-d'),
                    'start_time' => $entry->start_time,
                    'end_time' => $entry->end_time,
                    'hours_taught' => $entry->hours_taught,
                    'session_title' => $entry->session_title,
                    'content_covered' => $entry->content_covered,
                    'objectives' => $entry->objectives,
                    'teaching_methods' => $entry->teaching_methods,
                    'homework' => $entry->homework,
                    'students_present' => $entry->students_present,
                    'students_absent' => $entry->students_absent,
                    'status' => $entry->status,
                    'created_at' => $entry->created_at?->format('Y-m-d H:i'),
                    'validated_at' => $entry->validated_at?->format('Y-m-d H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'une entrée
     */
    public function show($id)
    {
        try {
            $entry = $this->textbookEntryService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Entrée non trouvée',
                'error' => $e->getMessage(),
            ], 404); 
        }
    }

    /**
     * Créer une nouvelle entrée
     */
    public function store(CreateTextbookEntryRequest $request)
    {
        try {
            $entry = $this->textbookEntryService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Entrée créée avec succès',
                'data' => new TextbookEntryResource($entry),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une entrée
     */
    public function update(UpdateTextbookEntryRequest $request, $id)
    {
        try {
            $entry = $this->textbookEntryService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Entrée mise à jour avec succès',
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une entrée
     */
    public function destroy($id)
    {
        try {
            $this->textbookEntryService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Entrée supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publier une entrée
     */
    public function publish($id)
    {
        try {
            $entry = $this->textbookEntryService->publish($id);

            return response()->json([
                'success' => true,
                'message' => 'Entrée publiée avec succès',
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function validateEntry(Request $request, $id) {
        try {
            $entry = $this->textbookEntryService->validate($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Entrée validée avec succès',
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function byClassGroup(Request $request, $classGroupId) {
        try {
            $filters = $request->only(['start_date', 'end_date', 'status', 'per_page']);
            $entries = $this->textbookEntryService->getByClassGroup($classGroupId, $filters);

            return response()->json([
                'success' => true,
                'data' => TextbookEntryResource::collection($entries->items()),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function byProfessor(Request $request, $professorId) {
        try {
            $filters = $request->only(['start_date', 'end_date', 'status', 'per_page']);
            $entries = $this->textbookEntryService->getByProfessor($professorId, $filters);

            return response()->json([
                'success' => true,
                'data' => TextbookEntryResource::collection($entries->items()),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function statistics(Request $request) {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'class_group_id',
                'professor_id',
            ]);

            $stats = $this->textbookEntryService->getStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

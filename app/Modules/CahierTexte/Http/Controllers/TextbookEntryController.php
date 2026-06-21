<?php

namespace App\Modules\CahierTexte\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CahierTexte\Http\Requests\CreateTextbookEntryRequest;
use App\Modules\CahierTexte\Http\Requests\UpdateTextbookEntryRequest;
use App\Modules\CahierTexte\Http\Resources\TextbookEntryResource;
use App\Modules\CahierTexte\Services\TextbookEntryService;
use Illuminate\Http\Request;
use App\Modules\CahierTexte\Models\TextbookEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Modules\Inscription\Services\AcademicYearService;
use App\Modules\Cours\Models\Program;
use App\Modules\RH\Models\Contrat;
use App\Modules\EmploiDuTemps\Models\EmploiDuTemps;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\RH\Models\Professor;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Cours\Models\CourseElement;
use App\Modules\Inscription\Models\Department;
use App\Modules\CahierTexte\Models\ContratProgram;
use App\Modules\Inscription\Models\AcademicYear;

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
                        
                    ] : null,

                    'class_group' => $classGroup ? [
                        'id' => $classGroup->id,
                        'group_name' => $formattedClassName,  // Utiliser le nom formaté
                        'original_name' => $classGroup->group_name,  // Optionnel: garder le nom original
                    ] : null,

                    'program' => $program ? [
                        'id' => $program->id,
                        'semester' => $program->semester,
                        'quota_hours' => $program->total_hours ?? 0,
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


    public function professorTeachingSummary(Request $request){
        try {

            $academicYearId = $request->academic_year_id;
            $regroupement = $request->regroupement;

            /*
            |--------------------------------------------------------------------------
            | STEP 1 : Contrats filtrés
            |--------------------------------------------------------------------------
            */

            $contracts = Contrat::query()
                ->where('academic_year_id', $academicYearId)
                ->where('regroupement', $regroupement)
                ->whereNotNull('factures_normalisees')
                ->get([
                    'id',
                    'professor_id',
                    'cycle_id',
                    'academic_year_id',
                    'factures_normalisees'
                ]);

            if ($contracts->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $contractIds = $contracts->pluck('id');

            /*
            |--------------------------------------------------------------------------
            | STEP 2 : contrats_programs
            |--------------------------------------------------------------------------
            */

            $contractPrograms = ContratProgram::query()
                ->whereIn('contrat_id', $contractIds)
                ->get([
                    'contrat_id',
                    'program_id',
                    'number_monographie',
                    'amount_monographie'
                ]);

            $programIds = $contractPrograms->pluck('program_id')->unique();

            /*
            |--------------------------------------------------------------------------
            | STEP 3 : Calcul des heures validées
            |--------------------------------------------------------------------------
            */

            $hoursByProgram = EmploiDuTemps::query()
                ->whereIn('program_id', $programIds)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'validated')
                ->where('validated_by', 1)
                ->selectRaw('program_id, SUM(hours_taught) as total_hours')
                ->groupBy('program_id')
                ->get()
                ->keyBy('program_id');

            /*
            |--------------------------------------------------------------------------
            | STEP 4 : Refiltrer contrats_programs
            |--------------------------------------------------------------------------
            */

            $filteredPrograms = $contractPrograms
                ->filter(function ($cp) use ($hoursByProgram) {
                    return isset($hoursByProgram[$cp->program_id]);
                });

            /*
            |--------------------------------------------------------------------------
            | STEP 5 : Programs
            |--------------------------------------------------------------------------
            */

            $programs = Program::query()
                ->whereIn(
                    'id',
                    $filteredPrograms->pluck('program_id')
                )
                ->get([
                    'id',
                    'course_element_professor_id',
                    'semester'
                ])
                ->keyBy('id');

            $cepIds = $programs
                ->pluck('course_element_professor_id')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | STEP 6 : CourseElementProfessor
            |--------------------------------------------------------------------------
            */

            $courseElementProfessors = CourseElementProfessor::query()
                ->whereIn('id', $cepIds)
                ->get([
                    'id',
                    'course_element_id',
                    'professor_id'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | STEP 7 : Professors
            |--------------------------------------------------------------------------
            */

            $professorIds = $courseElementProfessors
                ->pluck('professor_id')
                ->unique();

            $professors = Professor::query()
                ->whereIn('id', $professorIds)
                ->get([
                    'id',
                    'first_name',
                    'last_name',
                    'bank',
                    'rib_number'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | STEP 8 : Course Elements
            |--------------------------------------------------------------------------
            */

            $courseElementIds = $courseElementProfessors
                ->pluck('course_element_id')
                ->unique();

            $courseElements = CourseElement::query()
                ->whereIn('id', $courseElementIds)
                ->get([
                    'id',
                    'name',
                    'hours'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | STEP 9 : Departments
            |--------------------------------------------------------------------------
            */

            $cycleIds = $contracts
                ->pluck('cycle_id')
                ->unique();

            $departments = Department::query()
                ->whereIn('cycle_id', $cycleIds)
                ->get([
                    'id',
                    'name',
                    'abbreviation'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | STEP 10 : Organisation des données
            |--------------------------------------------------------------------------
            */

            $result = [];

            foreach ($filteredPrograms as $cp) {

                $contract = $contracts
                    ->firstWhere('id', $cp->contrat_id);

                if (!$contract) {
                    continue;
                }

                $program = $programs[$cp->program_id] ?? null;

                if (!$program) {
                    continue;
                }

                $cep = $courseElementProfessors[
                    $program->course_element_professor_id
                ] ?? null;

                if (!$cep) {
                    continue;
                }

                $professor = $professors[
                    $cep->professor_id
                ] ?? null;

                $course = $courseElements[
                    $cep->course_element_id
                ] ?? null;

                $department = $departments[
                    $contract->department_id
                ] ?? null;

                $hours = $hoursByProgram[
                    $cp->program_id
                ]->total_hours ?? 0;

                $professorKey = $professor->id;

                if (!isset($result[$professorKey])) {

                    $result[$professorKey] = [
                        'professor' => [
                            'id' => $professor->id,
                            'first_name' => $professor->first_name,
                            'last_name' => $professor->last_name,
                            'full_name' => trim(
                                $professor->first_name . ' ' .
                                $professor->last_name
                            ),
                            'bank' => $professor->bank,
                            'rib_number' => $professor->rib_number,
                        ],

                        'department' => [
                            'id' => $department?->id,
                            'name' => $department?->name,
                            'abbreviation' => $department?->abbreviation,
                        ],

                        'contracts' => []
                    ];
                }

                $result[$professorKey]['contracts'][] = [

                    'contrat_id' => $contract->id,

                    'program_id' => $cp->program_id,

                    'semester' => $program->semester,

                    'course' => [
                        'id' => $course?->id,
                        'name' => $course?->name,
                        'hours' => $course?->hours,
                    ],

                    'teaching_hours' => $hours,

                    'number_monographie' => $cp->number_monographie,

                    'amount_monographie' => $cp->amount_monographie,

                    'factures_normalisees' => $contract->factures_normalisees,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => array_values($result)
            ]);

        } 
        catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function exportPaymentExcel(Request $request){
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'regroupement'     => 'required|integer|in:1,2',
        ]);

        try {

            Log::info('Début export état paiement vacation', [
                'academic_year_id' => $request->academic_year_id,
                'regroupement'     => $request->regroupement,
                'user_id'          => auth()->id(),
            ]);

            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | 1. Année académique
            |--------------------------------------------------------------------------
            */
            $academicYear = AcademicYear::find($request->academic_year_id);

            if (!$academicYear) {

                Log::warning('Année académique introuvable', [
                    'academic_year_id' => $request->academic_year_id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Année académique introuvable.',
                ], 404);
            }

            $regroupement      = (int) $request->regroupement;
            $regroupementLabel = $regroupement === 1 ? '1ER' : '2ÈME';

            $yearLabel = $academicYear->label
                ?? $academicYear->name
                ?? $academicYear->year
                ?? 'ANNEE';

            /*
            |--------------------------------------------------------------------------
            | 2. Contrats
            |--------------------------------------------------------------------------
            */
            $contracts = Contrat::query()
                ->where('academic_year_id', $academicYear->id)
                ->where('regroupement', $regroupement)
                ->get([
                    'id',
                    'professor_id',
                    'cycle_id',
                    'factures_normalisees'
                ]);

            if ($contracts->isEmpty()) {

                Log::warning('Aucun contrat trouvé', [
                    'academic_year_id' => $academicYear->id,
                    'regroupement'     => $regroupement,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Aucun contrat trouvé pour ce regroupement.',
                ], 404);
            }

            $contractIds = $contracts->pluck('id');

            /*
            |--------------------------------------------------------------------------
            | 3. ContratProgram
            |--------------------------------------------------------------------------
            */
            $contractPrograms = ContratProgram::query()
                ->whereIn('contrat_id', $contractIds)
                ->get([
                    'contrat_id',
                    'program_id',
                    'number_monographie',
                    'amount_monographie'
                ]);

            if ($contractPrograms->isEmpty()) {

                Log::warning('Aucun programme associé aux contrats', [
                    'contrat_ids' => $contractIds
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Aucun programme trouvé.',
                ], 404);
            }

            $programIds = $contractPrograms->pluck('program_id')->unique();

            /*
            |--------------------------------------------------------------------------
            | 4. Heures effectuées
            |--------------------------------------------------------------------------
            */
            $hoursByProgram = \App\Modules\CahierTexte\Models\TextbookEntry::query()
                ->whereIn('program_id', $programIds)
                ->where('status', 'validated')
                ->selectRaw('program_id, SUM(hours_taught) as total_hours')
                ->groupBy('program_id')
                ->get()
                ->keyBy('program_id');

            /*
            |--------------------------------------------------------------------------
            | 5. Programmes
            |--------------------------------------------------------------------------
            */
            $programs = Program::query()
                ->whereIn('id', $programIds)
                ->get([
                    'id',
                    'course_element_professor_id',
                    'semester',
                ])
                ->keyBy('id');

            $cepIds = $programs
                ->pluck('course_element_professor_id')
                ->filter()
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | 6. CourseElementProfessor
            |--------------------------------------------------------------------------
            */
            $courseElementProfessors = CourseElementProfessor::query()
                ->whereIn('id', $cepIds)
                ->get([
                    'id',
                    'course_element_id',
                    'professor_id'
                ])
                ->keyBy('id');

            $professorIds = $courseElementProfessors
                ->pluck('professor_id')
                ->filter()
                ->unique();

            $courseElementIds = $courseElementProfessors
                ->pluck('course_element_id')
                ->filter()
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | 7. Professors
            |--------------------------------------------------------------------------
            */
            $professors = Professor::query()
                ->whereIn('id', $professorIds)
                ->get([
                    'id',
                    'first_name',
                    'last_name',
                    'bank',
                    'rib_number'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | 8. Course Elements
            |--------------------------------------------------------------------------
            */
            $courseElements = CourseElement::query()
                ->whereIn('id', $courseElementIds)
                ->get([
                    'id',
                    'name',
                    'code'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | 9. Departments
            |--------------------------------------------------------------------------
            */
            $cycleIds = $contracts
                ->pluck('cycle_id')
                ->filter()
                ->unique();

            $departments = Department::query()
                ->whereIn('cycle_id', $cycleIds)
                ->get([
                    'id',
                    'name',
                    'abbreviation'
                ])
                ->keyBy('id');

            /*
            |--------------------------------------------------------------------------
            | 10. Construction des lignes
            |--------------------------------------------------------------------------
            */
            $rows = [];

            foreach ($contractPrograms as $cp) {

                try {

                    $contract = $contracts->firstWhere('id', $cp->contrat_id);

                    if (!$contract) {
                        Log::warning('Contrat introuvable dans la boucle', [
                            'contrat_id' => $cp->contrat_id
                        ]);
                        continue;
                    }

                    $program = $programs[$cp->program_id] ?? null;

                    if (!$program) {
                        Log::warning('Programme introuvable', [
                            'program_id' => $cp->program_id
                        ]);
                        continue;
                    }

                    $cep = $courseElementProfessors[$program->course_element_professor_id] ?? null;

                    if (!$cep) {
                        Log::warning('CourseElementProfessor introuvable', [
                            'cep_id' => $program->course_element_professor_id
                        ]);
                        continue;
                    }

                    $professor  = $professors[$cep->professor_id] ?? null;
                    $course     = $courseElements[$cep->course_element_id] ?? null;
                    $department = $departments[$contract->cycle_id] ?? null;

                    $hoursEffectuees = (float) ($hoursByProgram[$cp->program_id]->total_hours ?? 0);
                    $hoursPlanned    = (float) ($program->quota_hours ?? 0);
                    $tauxHoraire     = (float) ($professor->hourly_rate ?? 6000);

                    $montantHeures = $hoursEffectuees * $tauxHoraire;

                    $nbMonographies = (int) ($cp->number_monographie ?? 0);

                    $tauxMonographie = $nbMonographies > 0
                        ? (float) ($cp->amount_monographie ?? 4000)
                        : 0;

                    $montantMonographies = $nbMonographies * $tauxMonographie;

                    $profKey = $professor->id ?? uniqid('prof_');

                    if (!isset($rows[$profKey])) {

                        $rows[$profKey] = [
                            'professor_name' => trim(
                                strtoupper($professor->last_name ?? '') . ' ' .
                                ($professor->first_name ?? '')
                            ),
                            'rib_number'    => $professor->rib_number ?? '',
                            'bank'          => $professor->bank ?? '',
                            'courses'       => [],
                            'montant_total' => 0,
                        ];
                    }

                    $rows[$profKey]['courses'][] = [
                        'course_name'          => $course->name ?? '',
                        'filiere'              => $department->abbreviation
                            ?? $department->name
                            ?? '',
                        'hours_planned'        => $hoursPlanned,
                        'hours_done'           => $hoursEffectuees,
                        'taux_horaire'         => $tauxHoraire,
                        'montant_heures'       => $montantHeures,
                        'nb_monographies'      => $nbMonographies,
                        'taux_monographie'     => $tauxMonographie,
                        'montant_monographies' => $montantMonographies,
                    ];

                    $rows[$profKey]['montant_total'] +=
                        $montantHeures + $montantMonographies;

                } catch (\Throwable $innerException) {

                    Log::error('Erreur lors du traitement d’un programme', [
                        'contract_program' => $cp,
                        'message'          => $innerException->getMessage(),
                        'trace'            => $innerException->getTraceAsString(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Tri
            |--------------------------------------------------------------------------
            */
            $rows = array_values($rows);

            usort($rows, function ($a, $b) {
                return strcmp($a['professor_name'], $b['professor_name']);
            });

            /*
            |--------------------------------------------------------------------------
            | 11. Génération Excel
            |--------------------------------------------------------------------------
            */


            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Feuil1');

            // ──────────────────────────────────────────────
            // STYLES RÉUTILISABLES
            // ──────────────────────────────────────────────

            $arialBold11 = [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
            ];

            $tnr11 = [
                'font' => ['name' => 'Times New Roman', 'size' => 11],
            ];

            $tnr11Bold = [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
            ];

            $centerWrap = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ];

            $leftWrap = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ];

            $thinBorder = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF000000'],
                    ],
                ],
            ];

            // ──────────────────────────────────────────────
            // EN-TÊTE INSTITUTION (lignes 3-7)
            // ──────────────────────────────────────────────

            $sheet->setCellValue('A3', "UNIVERSITE D'ABOMEY-CALAVI");
            $sheet->getStyle('A3')->applyFromArray(['font' => ['name' => 'Arial', 'size' => 11]]);
            $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('A4', "ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI");
            $sheet->getStyle('A4')->applyFromArray($arialBold11);

            $sheet->setCellValue('A5', 'CENTRE AUTONOME DE PERFECTIONNEMENT');
            $sheet->getStyle('A5')->applyFromArray($arialBold11);

            // Titre principal sur toute la largeur
            $sheet->mergeCells('A6:N6');
            $titreDoc = 'ETAT DES INDEMNITES DES HEURES DE VACATION DES ENSEIGNANTS, DU '
                . strtoupper($regroupementLabel)
                . ' REGROUPEMENT ' . $yearLabel;
            $sheet->setCellValue('A6', $titreDoc);
            $sheet->getStyle('A6')->applyFromArray([
                'font'      => ['name' => 'Arial Black', 'size' => 11],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ]);
            $sheet->getRowDimension(6)->setRowHeight(37.5);

            $sheet->setCellValue('H7', 'Abomey-Calavi, le …………………………');
            $sheet->getStyle('H7')->applyFromArray($arialBold11);
            $sheet->getStyle('H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ──────────────────────────────────────────────
            // EN-TÊTES DE COLONNES (ligne 9)
            // ──────────────────────────────────────────────

            $colHeaders = [
                'A' => 'N°',
                'B' => 'NOM & PRENOMS',
                'C' => 'MATIERES ENSEIGNEES',
                'D' => 'FILIERES',
                'E' => 'MASSE HORAIRE PREVUE ',
                'F' => 'MASSE HORAIRE EFFECTUEE',
                'G' => 'TAUX HORAIRES',
                'H' => 'MONTANT DES HEURES',
                'I' => 'NOMBRE DE MONOGRAPHIE',
                'J' => 'TAUX HORAIRES',
                'K' => 'MONTANT MONOGRAPHIES',
                'L' => 'MONTANT TOTAL A PAYER',
                'M' => 'NUMERO DE COMPTE',
                'N' => 'BANQUE',
            ];

            foreach ($colHeaders as $col => $label) {
                $sheet->setCellValue($col . '9', $label);
                $sheet->getStyle($col . '9')->applyFromArray(array_merge(
                    ['font' => ['bold' => true, 'name' => 'Arial', 'size' => 9]],
                    $centerWrap,
                    $thinBorder
                ));
            }
            $sheet->getRowDimension(9)->setRowHeight(37.5);

            // ──────────────────────────────────────────────
            // LARGEURS DE COLONNES (fidèles au template)
            // ──────────────────────────────────────────────

            $colWidths = [
                'A' => 5.43,  'B' => 25.57, 'C' => 25.71,
                'D' => 10.0,  'E' => 10.0,  'F' => 10.0,
                'G' => 10.14, 'H' => 13.43, 'I' => 10.0,
                'J' => 10.0,  'K' => 10.0,  'L' => 13.0,
                'M' => 17.71, 'N' => 14.0,
            ];
            foreach ($colWidths as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            // ──────────────────────────────────────────────
            // DONNÉES (une ligne par cours)
            // ──────────────────────────────────────────────

            $currentRow        = 10;
            $lineNumber        = 1;
            $totalHeures       = 0.0;
            $totalMonographies = 0.0;
            $totalGlobal       = 0.0;

            foreach ($rows as $profData) {
                $courses   = $profData['courses'];
                $nbCourses = count($courses);
                $startRow  = $currentRow;
                $endRow    = $currentRow + $nbCourses - 1;

                foreach ($courses as $idx => $course) {
                    $r = $currentRow;

                    // A – numéro séquentiel
                    $sheet->setCellValue('A' . $r, $lineNumber);
                    $sheet->getStyle('A' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // B – Nom & Prénoms (seulement sur la 1re ligne du professeur)
                    if ($idx === 0) {
                        $sheet->setCellValue('B' . $r, $profData['professor_name']);
                    }
                    $sheet->getStyle('B' . $r)->applyFromArray(array_merge($tnr11, $leftWrap, $thinBorder));

                    // C – Matière
                    $sheet->setCellValue('C' . $r, $course['course_name']);
                    $sheet->getStyle('C' . $r)->applyFromArray(array_merge($tnr11, $leftWrap, $thinBorder));

                    // D – Filière
                    $sheet->setCellValue('D' . $r, $course['filiere']);
                    $sheet->getStyle('D' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // E – Masse horaire prévue
                    $sheet->setCellValue('E' . $r, $course['hours_planned']);
                    $sheet->getStyle('E' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // F – Masse horaire effectuée
                    $sheet->setCellValue('F' . $r, $course['hours_done']);
                    $sheet->getStyle('F' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // G – Taux horaire heures
                    $sheet->setCellValue('G' . $r, $course['taux_horaire']);
                    $sheet->getStyle('G' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // H – Montant des heures
                    $sheet->setCellValue('H' . $r, $course['montant_heures']);
                    $sheet->getStyle('H' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // I – Nombre de monographies
                    $sheet->setCellValue('I' . $r, $course['nb_monographies']);
                    $sheet->getStyle('I' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // J – Taux monographie
                    $sheet->setCellValue('J' . $r, $course['taux_monographie']);
                    $sheet->getStyle('J' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // K – Montant monographies
                    $sheet->setCellValue('K' . $r, $course['montant_monographies']);
                    $sheet->getStyle('K' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    // L, M, N – style appliqué sur chaque ligne (la fusion gère l'affichage)
                    $sheet->getStyle('L' . $r)->applyFromArray(array_merge($tnr11Bold, $centerWrap, $thinBorder));
                    $sheet->getStyle('M' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));
                    $sheet->getStyle('N' . $r)->applyFromArray(array_merge($tnr11, $centerWrap, $thinBorder));

                    $sheet->getRowDimension($r)->setRowHeight(24.95);

                    $totalHeures       += (float) $course['montant_heures'];
                    $totalMonographies += (float) $course['montant_monographies'];
                    $currentRow++;
                    $lineNumber++;
                }

                // Fusion L / M / N pour les professeurs ayant plusieurs cours
                if ($nbCourses > 1) {
                    $sheet->mergeCells('L' . $startRow . ':L' . $endRow);
                    $sheet->mergeCells('M' . $startRow . ':M' . $endRow);
                    $sheet->mergeCells('N' . $startRow . ':N' . $endRow);
                }

                // Montant total du professeur (cellule fusionnée L, première ligne)
                $sheet->setCellValue('L' . $startRow, $profData['montant_total']);
                $sheet->getStyle('L' . $startRow)->applyFromArray(array_merge($tnr11Bold, $centerWrap));

                // Numéro de compte RIB
                $sheet->setCellValue('M' . $startRow, $profData['rib_number']);
                $sheet->getStyle('M' . $startRow)->applyFromArray(array_merge($tnr11, $centerWrap));

                // Banque
                $sheet->setCellValue('N' . $startRow, $profData['bank']);
                $sheet->getStyle('N' . $startRow)->applyFromArray(array_merge($tnr11, $centerWrap));

                $totalGlobal += (float) $profData['montant_total'];
            }

            // ──────────────────────────────────────────────
            // LIGNE TOTAL
            // ──────────────────────────────────────────────

            $totalRow = $currentRow;

            $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
            $sheet->setCellValue('A' . $totalRow,
                'TOTAL ………………………………………………………………………………………………………………………………………………');
            $sheet->getStyle('A' . $totalRow)->applyFromArray(array_merge($arialBold11, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]));

            $sheet->setCellValue('G' . $totalRow, '*****');
            $sheet->getStyle('G' . $totalRow)->applyFromArray(array_merge(
                ['font' => ['name' => 'Arial', 'size' => 11]],
                $centerWrap
            ));

            $sheet->setCellValue('H' . $totalRow, $totalHeures);
            $sheet->getStyle('H' . $totalRow)->applyFromArray(array_merge($arialBold11, [
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]));

            $sheet->setCellValue('K' . $totalRow, $totalMonographies);
            $sheet->getStyle('K' . $totalRow)->applyFromArray(array_merge($arialBold11, [
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]));

            $sheet->setCellValue('L' . $totalRow, $totalGlobal);
            $sheet->getStyle('L' . $totalRow)->applyFromArray($arialBold11);

            $sheet->getRowDimension($totalRow)->setRowHeight(24.95);

            // ──────────────────────────────────────────────
            // LIGNE ARRÊTÉ (montant en chiffres)
            // ──────────────────────────────────────────────

            $arretRow = $totalRow + 1;
            $sheet->mergeCells('A' . $arretRow . ':K' . ($arretRow + 1));
            $montantFormate  = number_format((int) $totalGlobal, 0, ',', ' ');
            $arretText = 'AARRETE LE PRESENT ETAT A LA SOMME DE : ' . $montantFormate . ' FRANCS CFA';
            $sheet->setCellValue('A' . $arretRow, $arretText);
            $sheet->getStyle('A' . $arretRow)->applyFromArray(array_merge($arialBold11, $leftWrap));
            $sheet->getRowDimension($arretRow)->setRowHeight(15.75);

            // ──────────────────────────────────────────────
            // MISE EN PAGE
            // ──────────────────────────────────────────────

            $sheet->getPageSetup()
                ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                ->setFitToWidth(1)
                ->setFitToHeight(0);

            $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);

            // ─────────────────────────────────────────────────────────────────
            // FIN DE SECTION — le code original reprend ici avec DB::commit();
            // ─────────────────────────────────────────────────────────────────

    

            Log::info('Export Excel généré avec succès', [
                'academic_year_id' => $academicYear->id,
                'regroupement'     => $regroupement,
                'nombre_professeurs' => count($rows),
            ]);

            Log::info('Données exportées (état paiement vacation)', [
                'academic_year_id'  => $academicYear->id,
                'regroupement'      => $regroupement,
                'total_professeurs' => count($rows),

                'professeurs' => array_map(function ($row) {

                    $rib = $row['rib_number'] ?? '';

                    return [
                        'professor_name' => $row['professor_name'],

                        // Masquage du RIB
                        'rib_number' => strlen($rib) > 6
                            ? substr($rib, 0, 3) . '************' . substr($rib, -3)
                            : '***',

                        'bank' => $row['bank'],

                        // Arrondi du montant
                        'montant_total' => round($row['montant_total'], 2),

                        'courses_count' => count($row['courses'] ?? []),
                    ];

                }, $rows),
            ]);


            $filename = "etat_paiement_vacation_{$regroupementLabel}_regroupement_{$yearLabel}.xlsx";

            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {

                try {
                    $writer->save('php://output');
                } catch (\Throwable $e) {

                    Log::error('Erreur écriture fichier Excel', [
                        'message' => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                    ]);

                    throw $e;
                }

            }, $filename, [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Erreur génération état paiement vacation', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du fichier Excel.',
                'error'   => config('app.debug')
                    ? $e->getMessage()
                    : 'Erreur interne du serveur'
            ], 500);
        }
    }
    
    

}

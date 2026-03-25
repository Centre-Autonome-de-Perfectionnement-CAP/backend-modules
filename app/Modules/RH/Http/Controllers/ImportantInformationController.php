<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\ImportantInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class ImportantInformationController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $informations = ImportantInformation::with('file')
            ->active()
            ->ordered()
            ->get()
            ->map(fn($info) => [
                'id' => $info->id,
                'title' => $info->title,
                'description' => $info->description,
                'icon' => $info->icon,
                'color' => $info->color,
                'link' => $info->link,
                'file' => $info->file ? [
                    'id' => $info->file->id,
                    'name' => $info->file->original_name,
                ] : null,
            ]);

        return $this->successResponse($informations);
    }

    public function indexAdmin(): JsonResponse
    {
        $informations = ImportantInformation::with('file')
            ->ordered()
            ->get()
            ->map(fn($info) => [
                'id' => $info->id,
                'title' => $info->title,
                'description' => $info->description,
                'icon' => $info->icon,
                'color' => $info->color,
                'link' => $info->link,
                'file_id' => $info->file_id,
                'file' => $info->file ? [
                    'id' => $info->file->id,
                    'name' => $info->file->original_name,
                ] : null,
                'is_active' => $info->is_active,
                'order' => $info->order,
                'created_at' => $info->created_at,
            ]);

        return $this->successResponse($informations);
    }

    public function store(Request $request): JsonResponse
    {
        // Convertir les valeurs string en boolean pour FormData
        if ($request->has('is_active') && is_string($request->is_active)) {
            $request->merge(['is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }
        
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'required|string',
            'color' => 'required|in:primary,success,info,warning,danger',
            'link' => 'nullable|string',
            'file_id' => 'nullable|exists:files,id',
            'file' => 'nullable|file|mimes:pdf|max:51200', // 50 MB
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        // Si un fichier est uploadé, on le stocke
        if ($request->hasFile('file')) {
            $fileStorageService = app(\App\Modules\Stockage\Services\FileStorageService::class);
            $uploadedFile = $fileStorageService->uploadFile(
                uploadedFile: $request->file('file'),
                userId: auth()->id(),
                visibility: 'public',
                collection: 'important_informations',
                moduleName: 'RH',
                moduleResourceType: 'ImportantInformation'
            );

            $uploadedFile->update([
                'original_name' => $data['title'],
                'description' => $data['description'],
                'is_official_document' => true,
                // Ne pas changer le disk, garder celui défini par FileStorageService
            ]);

            $data['file_id'] = $uploadedFile->id;
            unset($data['file']);
        }

        $information = ImportantInformation::create($data);
        return $this->successResponse($information->load('file'), 'Information créée avec succès', 201);
    }

    public function update(Request $request, ImportantInformation $important_information): JsonResponse
    {
        // Convertir les valeurs string en boolean pour FormData
        if ($request->has('is_active') && is_string($request->is_active)) {
            $request->merge(['is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }
        
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'icon' => 'sometimes|string',
            'color' => 'sometimes|in:primary,success,info,warning,danger',
            'link' => 'nullable|string',
            'file_id' => 'nullable|exists:files,id',
            'file' => 'nullable|file|mimes:pdf|max:51200', // 50 MB
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        // Si un fichier est uploadé, on le stocke
        if ($request->hasFile('file')) {
            $fileStorageService = app(\App\Modules\Stockage\Services\FileStorageService::class);
            $uploadedFile = $fileStorageService->uploadFile(
                uploadedFile: $request->file('file'),
                userId: auth()->id(),
                visibility: 'public',
                collection: 'important_informations',
                moduleName: 'RH',
                moduleResourceType: 'ImportantInformation'
            );

            $uploadedFile->update([
                'original_name' => $data['title'] ?? $important_information->title,
                'description' => $data['description'] ?? $important_information->description,
                'is_official_document' => true,
                // Ne pas changer le disk, garder celui défini par FileStorageService
            ]);

            $data['file_id'] = $uploadedFile->id;
            unset($data['file']);
        }

        $important_information->update($data);
        return $this->successResponse($important_information->load('file'), 'Information mise à jour');
    }

    public function destroy(ImportantInformation $important_information): JsonResponse
    {
        $important_information->delete();
        return $this->successResponse(null, 'Information supprimée');
    }

    public function broadcast(Request $request, ImportantInformation $important_information): JsonResponse
    {
        $data = $request->validate([
            'cycle_id' => 'required|exists:cycles,id',
            'department_ids' => 'required|array',
            'department_ids.*' => 'exists:departments,id',
            'levels' => 'required|array',
            'levels.*' => 'string',
            'all_departments' => 'boolean',
            'all_levels' => 'boolean',
        ]);

        try {
            \Log::info('Début broadcast information importante', [
                'information_id' => $important_information->id,
                'criteria' => $data,
            ]);

            // Récupérer l'année académique courante
            $currentAcademicYear = \App\Modules\Inscription\Models\AcademicYear::where('is_current', true)->first();
            
            if (!$currentAcademicYear) {
                \Log::warning('Aucune année académique courante trouvée');
                return $this->errorResponse('Aucune année académique courante définie', 400);
            }

            \Log::info('Année académique courante', [
                'academic_year_id' => $currentAcademicYear->id,
                'academic_year' => $currentAcademicYear->academic_year,
            ]);

            // Récupérer les étudiants via academic_paths pour avoir le niveau actuel
            $studentsQuery = \App\Modules\Inscription\Models\Student::query()
                ->with(['pendingStudents.personalInformation', 'pendingStudents.department'])
                ->whereHas('academicPaths', function ($pathQuery) use ($data, $currentAcademicYear) {
                    // Filtrer par année académique courante
                    $pathQuery->where('academic_year_id', $currentAcademicYear->id);

                    // Filtrer par niveau si spécifié
                    if (!$data['all_levels']) {
                        $pathQuery->whereIn('study_level', $data['levels']);
                    }

                    // Filtrer par département via studentPendingStudent
                    $pathQuery->whereHas('studentPendingStudent', function ($spsQuery) use ($data) {
                        $spsQuery->whereHas('pendingStudent', function ($psQuery) use ($data) {
                            $psQuery->where('status', 'approved');

                            // Filtrer par cycle via la relation department
                            $psQuery->whereHas('department', function ($deptQuery) use ($data) {
                                $deptQuery->where('cycle_id', $data['cycle_id']);
                            });

                            // Filtrer par département si spécifié
                            if (!$data['all_departments']) {
                                $psQuery->whereIn('department_id', $data['department_ids']);
                            }
                        });
                    });
                });

            \Log::info('Requête SQL construite', [
                'sql' => $studentsQuery->toSql(),
                'bindings' => $studentsQuery->getBindings(),
            ]);

            $students = $studentsQuery->get();

            \Log::info('Résultat de la requête', [
                'total_students' => $students->count(),
            ]);

            if ($students->isEmpty()) {
                // Logs détaillés pour comprendre pourquoi aucun étudiant n'est trouvé
                
                // 1. Vérifier les academic_paths dans l'année courante
                $pathsInYear = \App\Modules\Inscription\Models\AcademicPath::where('academic_year_id', $currentAcademicYear->id)->count();
                \Log::info('Academic paths dans l\'année courante', ['count' => $pathsInYear]);

                // 1b. Vérifier quelques exemples d'academic_paths
                $samplePaths = \App\Modules\Inscription\Models\AcademicPath::where('academic_year_id', $currentAcademicYear->id)
                    ->with('studentPendingStudent.pendingStudent.department')
                    ->limit(5)
                    ->get()
                    ->map(function($path) {
                        return [
                            'id' => $path->id,
                            'study_level' => $path->study_level,
                            'department_id' => $path->studentPendingStudent?->pendingStudent?->department_id,
                            'department_name' => $path->studentPendingStudent?->pendingStudent?->department?->name,
                            'cycle_id' => $path->studentPendingStudent?->pendingStudent?->department?->cycle_id,
                        ];
                    });
                \Log::info('Exemples d\'academic paths', ['samples' => $samplePaths]);

                // 2. Vérifier les départements du cycle 3
                $departmentsInCycle = \DB::table('departments')->where('cycle_id', $data['cycle_id'])->get(['id', 'name']);
                \Log::info('Départements dans le cycle', [
                    'cycle_id' => $data['cycle_id'],
                    'departments' => $departmentsInCycle,
                ]);

                // 3. Vérifier les pending_students dans ces départements
                $pendingStudentsInDepts = \DB::table('pending_students')
                    ->whereIn('department_id', $data['department_ids'])
                    ->where('status', 'approved')
                    ->count();
                \Log::info('Pending students dans les départements', [
                    'department_ids' => $data['department_ids'],
                    'count' => $pendingStudentsInDepts,
                ]);

                // 4. Vérifier les student_pending_student liés
                $spsCount = \DB::table('student_pending_student')
                    ->whereIn('pending_student_id', function($query) use ($data) {
                        $query->select('id')
                            ->from('pending_students')
                            ->whereIn('department_id', $data['department_ids'])
                            ->where('status', 'approved');
                    })
                    ->count();
                \Log::info('Student_pending_student liés', ['count' => $spsCount]);

                // 5. Vérifier les academic_paths pour ces student_pending_student
                $pathsForSps = \DB::table('academic_paths')
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->whereIn('student_pending_student_id', function($query) use ($data) {
                        $query->select('student_pending_student.id')
                            ->from('student_pending_student')
                            ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
                            ->whereIn('pending_students.department_id', $data['department_ids'])
                            ->where('pending_students.status', 'approved');
                    })
                    ->get(['id', 'study_level', 'student_pending_student_id']);
                \Log::info('Academic paths pour les départements sélectionnés', [
                    'count' => $pathsForSps->count(),
                    'samples' => $pathsForSps->take(5),
                ]);

                // 2. Vérifier les étudiants dans le cycle via academic_paths
                $studentsInCycle = \App\Modules\Inscription\Models\Student::query()
                    ->whereHas('academicPaths', function ($pathQuery) use ($data, $currentAcademicYear) {
                        $pathQuery->where('academic_year_id', $currentAcademicYear->id)
                            ->whereHas('studentPendingStudent.pendingStudent', function ($psQuery) use ($data) {
                                $psQuery->where('status', 'approved')
                                    ->whereHas('department', function ($deptQuery) use ($data) {
                                        $deptQuery->where('cycle_id', $data['cycle_id']);
                                    });
                            });
                    })->count();

                \Log::info('Étudiants dans le cycle (via academic_paths)', [
                    'cycle_id' => $data['cycle_id'],
                    'count' => $studentsInCycle,
                ]);

                // 3. Vérifier les niveaux disponibles dans academic_paths
                $availableLevels = \App\Modules\Inscription\Models\AcademicPath::where('academic_year_id', $currentAcademicYear->id)
                    ->whereHas('studentPendingStudent.pendingStudent', function ($psQuery) use ($data) {
                        $psQuery->where('status', 'approved')
                            ->whereHas('department', function ($deptQuery) use ($data) {
                                $deptQuery->where('cycle_id', $data['cycle_id']);
                            });
                        
                        if (!$data['all_departments']) {
                            $psQuery->whereIn('department_id', $data['department_ids']);
                        }
                    })
                    ->distinct()
                    ->pluck('study_level')
                    ->toArray();

                \Log::info('Niveaux disponibles dans academic_paths', [
                    'available_levels' => $availableLevels,
                    'requested_levels' => $data['levels'],
                ]);

                return $this->errorResponse('Aucun étudiant trouvé avec ces critères', 404);
            }

            // Préparer les données de l'information
            $informationData = [
                'title' => $important_information->title,
                'description' => $important_information->description,
                'link' => $important_information->link,
            ];

            // Récupérer le chemin du fichier si disponible
            $fileUrl = null;
            if ($important_information->file_id) {
                $file = $important_information->file;
                if ($file) {
                    $filePath = $file->file_path;
                    if (str_starts_with($filePath, 'public/')) {
                        $filePath = substr($filePath, 7);
                    }
                    $fileUrl = \Storage::disk($file->disk)->path($filePath);
                }
            }

            // Générer un ID unique pour ce broadcast
            $broadcastId = uniqid('broadcast_', true);

            // Initialiser le statut dans le cache
            \Cache::put("broadcast_status_{$broadcastId}", [
                'total_students' => $students->count(),
                'emails_sent' => 0,
                'emails_failed' => 0,
                'status' => 'queued',
                'started_at' => now(),
            ], now()->addHours(24));

            // Diviser les étudiants en chunks de 50 pour éviter les jobs trop lourds
            $chunks = $students->chunk(50);
            
            foreach ($chunks as $chunk) {
                // Dispatcher le job en queue
                \App\Modules\RH\Jobs\BroadcastImportantInformationJob::dispatch(
                    $chunk->toArray(),
                    $informationData,
                    $fileUrl,
                    $broadcastId
                )->onQueue('emails');
            }

            \Log::info('Broadcast d\'information importante lancé', [
                'broadcast_id' => $broadcastId,
                'information_id' => $important_information->id,
                'total_students' => $students->count(),
                'chunks' => $chunks->count(),
            ]);

            return $this->successResponse([
                'broadcast_id' => $broadcastId,
                'total_students' => $students->count(),
                'status' => 'queued',
                'message' => 'La diffusion a été mise en file d\'attente. Les emails seront envoyés progressivement.',
            ], "Diffusion lancée pour {$students->count()} étudiant(s)");

        } catch (\Exception $e) {
            \Log::error('Erreur diffusion information importante', [
                'information_id' => $important_information->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur lors de la diffusion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Récupère le statut d'un broadcast
     */
    public function getBroadcastStatus(string $broadcastId): JsonResponse
    {
        $status = \Cache::get("broadcast_status_{$broadcastId}");

        if (!$status) {
            return $this->errorResponse('Broadcast non trouvé ou expiré', 404);
        }

        return $this->successResponse($status);
    }
}

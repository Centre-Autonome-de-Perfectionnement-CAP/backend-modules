<?php

namespace App\Modules\Soutenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Soutenance\Http\Requests\CreateDefenseSubmissionRequest;
use App\Modules\Soutenance\Http\Requests\UpdateDefenseStatusRequest;
use App\Modules\Soutenance\Http\Requests\ScheduleDefenseRequest;
use App\Modules\Soutenance\Services\DefenseSubmissionService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefenseSubmissionController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected DefenseSubmissionService $defenseSubmissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'defense_type', 'department_id', 'academic_year_id']);
        $perPage = $this->getPerPage($request);
        
        $submissions = $this->defenseSubmissionService->getAll($filters, $perPage);

        return $this->successPaginatedResponse(
            $submissions,
            'Soumissions de soutenance récupérées avec succès'
        );
    }

    public function getData(Request $request)
    {
        $query = \App\Modules\Soutenance\Models\DefenseSubmission::with(['professor', 'period', 'department', 'student'])
            ->when($request->academic_year, function ($q) use ($request) {
                $q->whereHas('period', function ($q2) use ($request) {
                    $q2->where('academic_year_id', $request->academic_year);
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->defense_type, fn($q) => $q->where('defense_type', $request->defense_type))
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id));

        return datatables()->eloquent($query)
            ->addIndexColumn()
            ->addColumn('student_name', fn($row) => $row->first_names . ' ' . $row->last_name)
            ->addColumn('professor_name', fn($row) => $row->professor ? $row->professor->first_name . ' ' . $row->professor->last_name : '-')
            ->addColumn('actions', function($row) {
                $buttons = '';
                if ($row->status === 'pending') {
                    $buttons .= '<button class="btn btn-sm btn-success btn-accept m-1 p-3" data-id="'.$row->id.'">
                        <i class="fa fa-check"></i> Accepter
                    </button>';
                    $buttons .= '<button class="btn btn-sm btn-danger btn-reject m-1 p-3" data-id="'.$row->id.'">
                        <i class="fa fa-times"></i> Rejeter
                    </button>';
                }
                $buttons .= '<button class="btn btn-sm btn-info view-dossier" data-id="' . $row->id . '">
                    <i class="fa fa-eye"></i> Voir Dossier
                </button>';
                return $buttons;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function accept(Request $request): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($request->submission_id);
        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $this->defenseSubmissionService->updateStatus($submission, 'accepted');
        return $this->successResponse(null, 'Soumission acceptée avec succès');
    }

    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'submission_id' => 'required|exists:defense_submissions,id',
            'rejection_reason' => 'required|string|max:500'
        ]);

        $submission = $this->defenseSubmissionService->getById($request->submission_id);
        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $this->defenseSubmissionService->updateStatus($submission, 'rejected', $request->rejection_reason);
        return $this->successResponse(null, 'Soumission rejetée avec succès');
    }

    public function getDossierDetails($id)
    {
        $submission = $this->defenseSubmissionService->getById($id);
        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $documents = [];
        $files = $submission->files;

        if (is_array($files)) {
            if (!empty($files['thesis'])) {
                $documents[] = [
                    'name' => 'Mémoire',
                    'path' => \Storage::url($files['thesis']),
                    'filename' => basename($files['thesis'])
                ];
            }
            if (!empty($files['additional']) && is_array($files['additional'])) {
                foreach ($files['additional'] as $additionalFile) {
                    $documents[] = [
                        'name' => 'Document supplémentaire',
                        'path' => \Storage::url($additionalFile),
                        'filename' => basename($additionalFile)
                    ];
                }
            }
        }

        $contacts = [];
        if (is_array($submission->contacts)) {
            $contacts = $submission->contacts;
        } elseif (is_string($submission->contacts)) {
            $decoded = json_decode($submission->contacts, true);
            $contacts = is_array($decoded) ? $decoded : [];
        }

        return response()->json([
            'student_id_number' => $submission->student_id_number ?: 'Non Fourni',
            'last_name' => $submission->last_name ?? '',
            'first_names' => $submission->first_names ?? '',
            'email' => $submission->email ?? '',
            'contacts' => $contacts,
            'department' => $submission->department->name ?? '',
            'professor' => $submission->professor ? $submission->professor->first_name . ' ' . $submission->professor->last_name : '',
            'thesis_title' => $submission->thesis_title ?? '',
            'period' => $submission->period ? $submission->period->start_date->format('d/m/Y') . ' - ' . $submission->period->end_date->format('d/m/Y') : '',
            'defense_type' => $submission->defense_type ?? '',
            'status' => $submission->status ?? '',
            'documents' => $documents,
        ]);
    }

    public function store(CreateDefenseSubmissionRequest $request): JsonResponse
    {
        try {
            \Log::info('Defense Submission Request', [
                'validated' => $request->validated(),
                'all' => $request->all(),
                'files' => $request->allFiles()
            ]);

            $submission = $this->defenseSubmissionService->create(
                $request->validated(),
                $request->file('thesis_file'),
                $request->file('additional_files')
            );

            return $this->createdResponse(
                $submission,
                'Soumission de soutenance créée avec succès'
            );
        } catch (\Exception $e) {
            \Log::error('Defense Submission Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Erreur lors de la soumission: ' . $e->getMessage(), 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($id);

        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        return $this->successResponse($submission, 'Soumission récupérée avec succès');
    }

    public function updateStatus(UpdateDefenseStatusRequest $request, int $id): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($id);

        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $validated = $request->validated();
        $submission = $this->defenseSubmissionService->updateStatus(
            $submission,
            $validated['status'],
            $validated['rejection_reason'] ?? null
        );

        return $this->successResponse($submission, 'Statut mis à jour avec succès');
    }

    public function scheduleDefense(ScheduleDefenseRequest $request, int $id): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($id);

        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $submission = $this->defenseSubmissionService->scheduleDefense(
            $submission,
            $request->validated()
        );

        return $this->successResponse($submission, 'Soutenance planifiée avec succès');
    }

    public function destroy(int $id): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($id);

        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $this->defenseSubmissionService->delete($submission);

        return $this->successResponse(null, 'Soumission supprimée avec succès');
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->defenseSubmissionService->getStatistics();

        return $this->successResponse($stats, 'Statistiques récupérées avec succès');
    }
}

<?php

namespace App\Modules\LegacyStudent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyStudent\Models\LegacyStudent;
use App\Modules\LegacyStudent\Models\LegacyStudentServiceRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LegacyStudentAdminController extends Controller
{
    use ApiResponse;

    /**
     * Liste paginée des anciens étudiants avec statistiques et filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = LegacyStudent::with(['department', 'departments', 'services']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($year = $request->input('enrollment_year')) {
            $query->where('enrollment_year', $year);
        }

        if ($departmentId = $request->input('department_id')) {
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereHas('departments', fn($sub) => $sub->where('departments.id', $departmentId));
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $paginator = $query->latest()->paginate($perPage);

        // Stats globales
        $stats = [
            'total' => LegacyStudent::count(),
            'pending' => LegacyStudent::where('status', 'pending')->count(),
            'validated' => LegacyStudent::where('status', 'validated')->count(),
            'rejected' => LegacyStudent::where('status', 'rejected')->count(),
            'services_pending' => LegacyStudentServiceRequest::where('status', 'pending')->count(),
        ];

        // Formatage pour correspondre au format attendu par le frontend
        $formattedData = collect($paginator->items())->map(function ($item) {
            $filiere = $item->department ?? $item->departments->first();
            return [
                'id' => $item->id,
                'matricule' => $item->matricule,
                'last_name' => $item->last_name,
                'first_name' => $item->first_name,
                'date_of_birth' => $item->date_of_birth?->format('Y-m-d') ?? $item->date_of_birth,
                'place_of_birth' => $item->place_of_birth,
                'cycle' => $item->cycle,
                'email' => $item->email,
                'phone' => $item->phone,
                'enrollment_year' => $item->enrollment_year,
                'department' => $filiere ? [
                    'id' => $filiere->id,
                    'name' => $filiere->name,
                    'abbreviation' => $filiere->code ?? $filiere->abbreviation ?? 'N/A',
                    'cycle' => $item->cycle ?? 'Licence/Master',
                ] : null,
                'status' => $item->status,
                'rejection_reason' => $item->rejection_reason,
                'validated_by' => $item->validated_by,
                'validated_at' => $item->validated_at?->toIso8601String(),
                'notes' => $item->notes,
                'notes_admin' => $item->notes,
                'services_count' => $item->services->count(),
                'services_requested' => $item->services,
                'created_at' => $item->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'stats' => $stats,
        ]);
    }

    /**
     * Enregistrement manuel au guichet
     */
    public function store(Request $request): JsonResponse
    {
        $input = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($input, [
            'matricule' => 'required|string',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:150',
            'cycle' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'enrollment_year' => 'required|integer|max:2022',
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string',
            'notes_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['notes_admin'])) {
            $data['notes'] = $data['notes_admin'];
        }

        // 1. Vérification sur le MATRICULE
        $existingByMatricule = LegacyStudent::where('matricule', $data['matricule'])->first();
        if ($existingByMatricule) {
            if ($existingByMatricule->status === 'validated') {
                return response()->json([
                    'errors' => [
                        'matricule' => ["Ce matricule est déjà enregistré et validé dans le système."]
                    ]
                ], 422);
            }
            if ($existingByMatricule->status === 'pending') {
                return response()->json([
                    'errors' => [
                        'matricule' => ["Un dossier avec ce matricule est déjà en attente de validation."]
                    ]
                ], 422);
            }
            // Si rejeté, l'enregistrement au guichet écrase et valide le dossier
        }

        // 2. Vérification sur l'IDENTITÉ (Nom + Prénoms + Date de naissance)
        $normLastName = mb_strtolower(trim($data['last_name']));
        $firstWord = mb_strtolower(explode(' ', trim($data['first_name']))[0]);
        $identityQuery = LegacyStudent::query()
            ->whereRaw('LOWER(last_name) = ?', [$normLastName])
            ->whereRaw('LOWER(first_name) LIKE ?', ["%{$firstWord}%"]);

        if (!empty($data['date_of_birth'])) {
            $identityQuery->whereDate('date_of_birth', $data['date_of_birth']);
        }

        if ($existingByMatricule) {
            $identityQuery->where('id', '!=', $existingByMatricule->id);
        }

        $existingByIdentity = $identityQuery->first();
        if ($existingByIdentity) {
            if ($existingByIdentity->status === 'validated' || $existingByIdentity->status === 'pending') {
                $statusLabel = $existingByIdentity->status === 'validated' ? 'validé' : 'en attente';
                return response()->json([
                    'errors' => [
                        'last_name' => ["Un dossier {$statusLabel} existe déjà pour cette identité (Matricule: {$existingByIdentity->matricule})."]
                    ]
                ], 422);
            }
        }

        $data['status'] = 'validated'; // Création directe validée par l'admin
        $user = $request->user();
        $data['validated_by'] = $user ? "{$user->first_name} {$user->last_name}" : 'Secrétariat Scolarité';
        $data['validated_at'] = now();
        $data['rejection_reason'] = null;

        if ($existingByMatricule) {
            $existingByMatricule->update($data);
            $student = $existingByMatricule;
        } else {
            $student = LegacyStudent::create($data);
        }

        if (!empty($data['department_id'])) {
            $student->departments()->sync([$data['department_id']]);
        }

        return response()->json($student, 201);
    }

    /**
     * Mise à jour d'un dossier ancien étudiant
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $student = LegacyStudent::findOrFail($id);
        $input = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($input, [
            'matricule' => "required|string|unique:legacy_students,matricule,{$id}",
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:150',
            'cycle' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'enrollment_year' => 'required|integer|max:2022',
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string',
            'notes_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['notes_admin'])) {
            $data['notes'] = $data['notes_admin'];
        }

        $student->update($data);

        if (!empty($data['department_id'])) {
            $student->departments()->sync([$data['department_id']]);
        }

        return response()->json(['success' => true, 'data' => $student]);
    }

    /**
     * Validation d'un dossier
     */
    public function validateStudent(Request $request, int $id): JsonResponse
    {
        $student = LegacyStudent::findOrFail($id);
        $user = $request->user();
        $validatorName = $user ? "{$user->first_name} {$user->last_name}" : 'Secrétariat Scolarité';

        $student->update([
            'status' => 'validated',
            'validated_by' => $validatorName,
            'validated_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json(['success' => true, 'data' => $student]);
    }

    /**
     * Rejet d'un dossier avec motif
     */
    public function rejectStudent(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = LegacyStudent::findOrFail($id);
        $student->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json(['success' => true, 'data' => $student]);
    }

    /**
     * Validation en masse
     */
    public function bulkValidate(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $user = $request->user();
        $validatorName = $user ? "{$user->first_name} {$user->last_name}" : 'Secrétariat Scolarité';

        LegacyStudent::whereIn('id', $ids)->update([
            'status' => 'validated',
            'validated_by' => $validatorName,
            'validated_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    /**
     * Rejet en masse
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $reason = $request->input('reason', 'Rejet administratif');

        LegacyStudent::whereIn('id', $ids)->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    /**
     * Liste des demandes de services étudiants
     */
    public function servicesIndex(Request $request): JsonResponse
    {
        $query = LegacyStudentServiceRequest::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('student_name', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($serviceType = $request->input('service_type')) {
            $query->where('service_type', $serviceType);
        }

        $services = $query->latest()->get();

        $stats = [
            'total' => LegacyStudentServiceRequest::count(),
            'pending' => LegacyStudentServiceRequest::where('status', 'pending')->count(),
            'in_progress' => LegacyStudentServiceRequest::where('status', 'in_progress')->count(),
            'delivered' => LegacyStudentServiceRequest::where('status', 'delivered')->count(),
            'rejected' => LegacyStudentServiceRequest::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'data' => $services,
            'stats' => $stats,
        ]);
    }

    /**
     * Mise à jour du statut d'une demande de service
     */
    public function updateServiceStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,delivered,rejected',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = LegacyStudentServiceRequest::findOrFail($id);
        $user = $request->user();
        $processor = $user ? "{$user->first_name} {$user->last_name}" : 'Scolarité CAP';

        $service->update([
            'status' => $request->input('status'),
            'rejection_reason' => $request->input('reason'),
            'processed_by' => $processor,
            'processed_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => $service]);
    }

    // ── Dossier académique rétroactif ────────────────────────────────────────

    /**
     * Liste les relevés académiques d'un ancien étudiant
     */
    public function academicRecordsIndex(int $id): JsonResponse
    {
        $student = LegacyStudent::findOrFail($id);
        $records = $student->academicRecords()->orderBy('academic_year', 'asc')->get();
        return $this->successResponse($records, 'Relevés académiques récupérés');
    }

    /**
     * Crée un nouveau relevé académique pour un ancien étudiant
     */
    public function academicRecordsStore(Request $request, int $id): JsonResponse
    {
        $student = LegacyStudent::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'academic_year'    => 'required|string|max:20',
            'level'            => 'nullable|string|max:100',
            'semester'         => 'nullable|string|max:20',
            'general_average'  => 'nullable|numeric|min:0|max:20',
            'total_credits'    => 'nullable|integer|min:0',
            'obtained_credits' => 'nullable|integer|min:0',
            'decision'         => 'nullable|string|max:50',
            'mention'          => 'nullable|string|max:50',
            'thesis_title'     => 'nullable|string|max:500',
            'thesis_grade'     => 'nullable|numeric|min:0|max:20',
            'thesis_date'      => 'nullable|date',
            'quitus_accorded'  => 'nullable|boolean',
            'courses'          => 'nullable|array',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Empêcher les doublons d'année académique pour le même étudiant
        $exists = $student->academicRecords()
            ->where('academic_year', $request->academic_year)
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->semester))
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                "Un relevé pour l'année {$request->academic_year} existe déjà pour cet étudiant.",
                422
            );
        }

        $record = $student->academicRecords()->create([
            'academic_year'    => $request->academic_year,
            'level'            => $request->level,
            'semester'         => $request->semester,
            'general_average'  => $request->general_average,
            'total_credits'    => $request->total_credits,
            'obtained_credits' => $request->obtained_credits,
            'decision'         => $request->decision,
            'mention'          => $request->mention,
            'thesis_title'     => $request->thesis_title,
            'thesis_grade'     => $request->thesis_grade,
            'thesis_date'      => $request->thesis_date,
            'quitus_accorded'  => $request->boolean('quitus_accorded', false),
            'courses'          => $request->courses ?? [],
            'notes'            => $request->notes,
        ]);

        return $this->successResponse($record, 'Relevé académique créé avec succès', 201);
    }

    /**
     * Met à jour un relevé académique existant
     */
    public function academicRecordsUpdate(Request $request, int $id, int $recordId): JsonResponse
    {
        $student = LegacyStudent::findOrFail($id);
        $record  = $student->academicRecords()->findOrFail($recordId);

        $validator = Validator::make($request->all(), [
            'academic_year'    => 'sometimes|required|string|max:20',
            'level'            => 'nullable|string|max:100',
            'semester'         => 'nullable|string|max:20',
            'general_average'  => 'nullable|numeric|min:0|max:20',
            'total_credits'    => 'nullable|integer|min:0',
            'obtained_credits' => 'nullable|integer|min:0',
            'decision'         => 'nullable|string|max:50',
            'mention'          => 'nullable|string|max:50',
            'thesis_title'     => 'nullable|string|max:500',
            'thesis_grade'     => 'nullable|numeric|min:0|max:20',
            'thesis_date'      => 'nullable|date',
            'quitus_accorded'  => 'nullable|boolean',
            'courses'          => 'nullable|array',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $record->update(array_merge(
            $request->only([
                'academic_year', 'level', 'semester', 'general_average',
                'total_credits', 'obtained_credits', 'decision', 'mention',
                'thesis_title', 'thesis_grade', 'thesis_date', 'notes',
            ]),
            [
                'quitus_accorded' => $request->boolean('quitus_accorded', $record->quitus_accorded),
                'courses'         => $request->has('courses') ? $request->courses : $record->courses,
            ]
        ));

        return $this->successResponse($record->fresh(), 'Relevé académique mis à jour');
    }

    /**
     * Supprime un relevé académique
     */
    public function academicRecordsDestroy(int $id, int $recordId): JsonResponse
    {
        $student = LegacyStudent::findOrFail($id);
        $record  = $student->academicRecords()->findOrFail($recordId);
        $record->delete();

        return $this->successResponse(null, 'Relevé académique supprimé');
    }

    /**
     * Importation d'un fichier Excel d'anciens étudiants
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new \App\Modules\LegacyStudent\Imports\LegacyStudentsImport();

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'success'        => true,
                'message'        => 'Fichier traité avec succès.',
                'imported_count' => count($import->created),
                'created'        => $import->created,
                'duplicates'     => $import->duplicates,
                'errors'         => $import->errors,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du fichier : ' . $e->getMessage(),
            ], 500);
        }
    }
}



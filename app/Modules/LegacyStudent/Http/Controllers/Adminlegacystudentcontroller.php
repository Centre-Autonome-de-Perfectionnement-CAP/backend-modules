<?php

namespace App\Modules\LegacyStudent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyStudent\Http\Requests\UpdateLegacyStudentRequest;
use App\Modules\LegacyStudent\Imports\LegacyStudentsImport;
use App\Models\LegacyStudent;
use App\Modules\Inscription\Models\Department;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AdminLegacyStudentController extends Controller
{
    use ApiResponse;
    /**
     * GET /api/v1/admin/legacy-students
     *
     * Listing paginé + filtres + KPIs, pour LegacyStudentsTable.tsx (Dev 5)
     * et LegacyStudentsStats.tsx (Dev 5), consommés via Dev 6.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LegacyStudent::query()->with('departments');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($year = $request->query('year')) {
            $query->where('enrollment_year', $year);
        }

        if ($departmentId = $request->query('department_id')) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        if ($keyword = $request->query('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('matricule', 'like', "%{$keyword}%")
                  ->orWhere('first_name', 'like', "%{$keyword}%")
                  ->orWhere('last_name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        // KPIs calculés indépendamment des filtres appliqués (vue d'ensemble globale)
        $kpis = [
            'total'     => LegacyStudent::count(),
            'pending'   => LegacyStudent::where('status', 'pending')->count(),
            'validated' => LegacyStudent::where('status', 'validated')->count(),
            'rejected'  => LegacyStudent::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Dossiers récupérés avec succès',
            'data'    => $paginated->items(),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
            'kpis' => $kpis,
        ]);
    }

    /**
     * GET /api/v1/admin/legacy-students/{id}
     */
    public function show(int $id): JsonResponse
    {
        $legacyStudent = LegacyStudent::with('departments')->findOrFail($id);

        // TODO: brancher l'historique des services demandés + journal d'audit
        // dès que ces modules/tables existent (mentionné dans la modale "Détails"
        // du Dev 5 : LegacyStudentDetailModal.tsx).

        return $this->successResponse($legacyStudent, 'Dossier récupéré avec succès');
    }

    /**
     * POST /api/v1/admin/legacy-students
     *
     * Création manuelle par la secrétaire (guichet).
     * Réutilise les mêmes règles de validation que l'inscription publique,
     * on passe donc par la même logique de validation à la main ici
     * (le doc ne prévoit pas de Request dédiée pour ce endpoint précis).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matricule'         => ['required', 'string', 'max:50', 'unique:legacy_students,matricule'],
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:150'],
            'phone'             => ['required', 'string', 'max:20'],
            'enrollment_year'   => ['required', 'integer', 'min:1970', 'max:2022'],
            'department_ids'    => ['required', 'array', 'min:1'],
            'department_ids.*'  => ['string', 'uuid', 'exists:departments,id'],
            'notes_admin'       => ['nullable', 'string', 'max:2000'],
        ]);

        $legacyStudent = DB::transaction(function () use ($validated) {
            $student = LegacyStudent::create([
                'matricule'       => $validated['matricule'],
                'first_name'      => $validated['first_name'],
                'last_name'       => $validated['last_name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'enrollment_year' => $validated['enrollment_year'],
                'notes_admin'     => $validated['notes_admin'] ?? null,
                'status'          => 'pending',
            ]);

            $student->departments()->attach(
                Department::whereIn('id', $validated['department_ids'])
                    ->get()
                    ->mapWithKeys(fn ($department) => [
                        $department->id => ['cycle_id' => $department->cycle_id],
                    ])
                    ->toArray()
            );

            return $student;
        });

        return $this->createdResponse($legacyStudent->load('departments'), 'Dossier créé avec succès.');
    }

    /**
     * PUT /api/v1/admin/legacy-students/{id}
     */
    public function update(UpdateLegacyStudentRequest $request, int $id): JsonResponse
    {
        $legacyStudent = LegacyStudent::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($legacyStudent, $validated) {
            $legacyStudent->update(collect($validated)->except('department_ids')->toArray());

            if (array_key_exists('department_ids', $validated)) {
                $pivotData = Department::whereIn('id', $validated['department_ids'])
                    ->get()
                    ->mapWithKeys(fn ($department) => [
                        $department->id => ['cycle_id' => $department->cycle_id],
                    ])
                    ->toArray();

                $legacyStudent->departments()->sync($pivotData);
            }
        });

        return $this->updatedResponse($legacyStudent->fresh('departments'), 'Dossier mis à jour avec succès.');
    }

    /**
     * PATCH /api/v1/admin/legacy-students/{id}/status
     *
     * Validation ou rejet (motif obligatoire si rejet).
     * Enregistre automatiquement qui a validé/rejeté et quand.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status'            => ['required', Rule::in(['validated', 'rejected'])],
            'rejection_reason'  => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
        ], [
            'rejection_reason.required_if' => 'Le motif de rejet est obligatoire.',
        ]);

        $legacyStudent = LegacyStudent::findOrFail($id);

        $legacyStudent->update([
            'status'           => $validated['status'],
            'rejection_reason' => $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null,
            'validated_by'     => $request->user()->id,
            'validated_at'     => now(),
        ]);

        $message = $validated['status'] === 'validated'
            ? 'Dossier validé avec succès.'
            : 'Dossier rejeté avec succès.';

        return $this->successResponse($legacyStudent->fresh(), $message);
    }

    /**
     * POST /api/v1/admin/legacy-students/bulk-status
     *
     * Validation/rejet groupé de plusieurs dossiers à la fois.
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'               => ['required', 'array', 'min:1'],
            'ids.*'             => ['integer', 'exists:legacy_students,id'],
            'status'            => ['required', Rule::in(['validated', 'rejected'])],
            'rejection_reason'  => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
        ], [
            'rejection_reason.required_if' => 'Le motif de rejet est obligatoire.',
        ]);

        $updated = DB::transaction(function () use ($request, $validated) {
            return LegacyStudent::whereIn('id', $validated['ids'])->update([
                'status'           => $validated['status'],
                'rejection_reason' => $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null,
                'validated_by'     => $request->user()->id,
                'validated_at'     => now(),
            ]);
        });

        return $this->successResponse(null, "{$updated} dossier(s) mis à jour avec succès.");
    }

    /**
     * GET /api/v1/admin/legacy-students/export
     *
     * Renvoie les données brutes filtrées ; la génération du fichier .xlsx
     * elle-même se fait côté frontend par le Dev 6 (exportExcel.ts).
     */
    public function export(Request $request): JsonResponse
    {
        $query = LegacyStudent::query()->with('departments');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($year = $request->query('year')) {
            $query->where('enrollment_year', $year);
        }

        return $this->successResponse($query->get(), 'Données exportées avec succès');
    }

    /**
     * POST /api/v1/admin/legacy-students/import
     *
     * Réception d'un fichier Excel pour import massif.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new LegacyStudentsImport();
        Excel::import($import, $request->file('file'));

        return $this->successResponse([
            'created_count'    => count($import->created),
            'duplicate_count'  => count($import->duplicates),
            'error_count'      => count($import->errors),
            'duplicates'       => $import->duplicates,
            'errors'           => $import->errors,
        ], count($import->created) . ' dossier(s) importé(s) avec succès.');
    }
}
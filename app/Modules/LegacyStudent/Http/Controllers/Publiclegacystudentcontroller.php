<?php

namespace App\Modules\LegacyStudent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyStudent\Http\Requests\RegisterLegacyStudentRequest;
use App\Services\StudentLookupService;
use App\Modules\Inscription\Models\Department;
use App\Models\LegacyStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicLegacyStudentController extends Controller
{
    use ApiResponse;

    public function __construct(protected StudentLookupService $lookupService)
    {
    }

    /**
     * GET /api/v1/legacy-students/check/{matricule}
     *
     * Vérifie si un matricule existe (dans students OU legacy_students).
     * Utilisé par useStudentLookup.ts (Dev 4) pour décider s'il faut
     * ouvrir la modal de déclaration.
     */
    public function check(string $matricule): JsonResponse
    {
        $result = $this->lookupService->lookup($matricule);

        if (! $result['found']) {
            return $this->errorResponse(
                'Aucun étudiant trouvé avec ce matricule.',
                404,
                $result['error_code']
            );
        }

        return $this->successResponse([
            'source'  => $result['source'],
            'student' => $result['student'],
        ], 'Étudiant trouvé avec succès');
    }

    /**
     * GET /api/v1/legacy-students/available-filieres
     *
     * Alimente la liste déroulante / multi-select du Dev 3 (FiliereMultiSelect.tsx).
     */
    public function availableFilieres(): JsonResponse
    {
        // ⚠️ Ajuste selon la vraie structure de la table departments existante.
        $departments = Department::query()
            ->where('is_active', true) // adapte le nom de colonne si différent
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return $this->successResponse($departments, 'Filières récupérées avec succès');
    }

    /**
     * POST /api/v1/legacy-students/register
     *
     * Auto-déclaration d'un ancien étudiant (< 2023).
     * Ne doit JAMAIS bloquer l'étudiant : on répond immédiatement
     * avec can_continue: true, même si le dossier reste "pending".
     */
    public function register(RegisterLegacyStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $legacyStudent = DB::transaction(function () use ($validated) {
            $student = LegacyStudent::create([
                'matricule'       => $validated['matricule'],
                'first_name'      => $validated['first_name'],
                'last_name'       => $validated['last_name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'],
                'enrollment_year' => $validated['enrollment_year'],
                'status'          => 'pending',
            ]);

            // On récupère le cycle_id de chaque département automatiquement,
            // pas besoin que l'utilisateur le précise (une filière n'a qu'un
            // seul cycle possible, fixé dans la table departments).
            $departments = Department::whereIn('id', $validated['department_ids'])->get();

            $pivotData = $departments->mapWithKeys(fn ($department) => [
                $department->id => ['cycle_id' => $department->cycle_id],
            ])->toArray();

            $student->departments()->attach($pivotData);

            return $student;
        });

        return $this->createdResponse([
            'can_continue' => true,
            'id'           => $legacyStudent->id,
            'matricule'    => $legacyStudent->matricule,
            'status'       => $legacyStudent->status,
        ], 'Votre dossier a été enregistré et est en cours de validation.');
    }
}
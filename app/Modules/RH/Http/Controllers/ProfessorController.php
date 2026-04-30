<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Professor;
use App\Modules\RH\Http\Requests\CreateProfessorRequest;
use App\Modules\RH\Http\Requests\UpdateProfessorRequest;
use App\Modules\RH\Http\Resources\ProfessorResource;
use App\Modules\RH\Services\ProfessorService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ProfessorController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected ProfessorService $professorService
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
    }

    // ───────────────────────── LISTE
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'grade_id', 'bank',
                'sort_by', 'sort_order',
                'nationality', 'city', 'profession'
            ]);

            $perPage = $this->getPerPage($request);

            $professors = $this->professorService->getAll($filters, $perPage);

            $professors->setCollection(
                ProfessorResource::collection($professors->getCollection())->collection
            );

            return $this->successPaginatedResponse(
                $professors,
                'Professeurs récupérés avec succès'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des professeurs',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── CREATE
    public function store(CreateProfessorRequest $request): JsonResponse
{

        try {
            $data = $request->validated();

            // ✅ CORRECTION : passer les fichiers au service
            // Le service s'occupe lui-même du stockage des fichiers
            $ribFile = $request->file('rib');
            $ifuFile = $request->file('ifu');

            $professor = $this->professorService->create($data, Auth::id(), $ribFile, $ifuFile);

            return $this->createdResponse(
                new ProfessorResource($professor),
                'Professeur créé avec succès'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la création du professeur',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── SHOW
    public function show(Professor $professor): JsonResponse
    {
        try {
            return $this->successResponse(
                new ProfessorResource($professor->load('grade')),
                'Professeur récupéré avec succès'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération du professeur',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── UPDATE
    public function update(UpdateProfessorRequest $request, Professor $professor): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $ribFile = $request->file('rib');
            $ifuFile = $request->file('ifu');

            $professor = $this->professorService->update(
                $professor,
                $data,
                Auth::id(),
                $ribFile,
                $ifuFile
            );

            DB::commit();

            return $this->updatedResponse(
                new ProfessorResource($professor->load('grade')),
                'Professeur mis à jour avec succès'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Erreur lors de la mise à jour du professeur',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── DELETE
    public function destroy(Professor $professor): JsonResponse
    {
        try {
            $this->professorService->delete($professor);

            return $this->deletedResponse('Professeur supprimé avec succès');
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la suppression du professeur',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── LISTE DES BANQUES
    public function getBanks(): JsonResponse
    {
        try {
            $banks = Professor::whereNotNull('bank')
                ->where('bank', '!=', '')
                ->distinct()
                ->pluck('bank')
                ->sort()
                ->values();

            return $this->successResponse(
                $banks,
                'Banques récupérées avec succès'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des banques',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── UPDATE ADDRESS / INFO
    public function updateAddress(Request $request, Professor $professor): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nationality'  => 'nullable|string|max:100',
                'profession'   => 'nullable|string|max:100',
                'city'         => 'nullable|string|max:100',
                'district'     => 'nullable|string|max:100',
                'plot_number'  => 'nullable|string|max:100',
                'house_number' => 'nullable|string|max:100',
            ]);

            $professor->update($validated);

            return $this->updatedResponse(
                new ProfessorResource($professor),
                'Informations mises à jour avec succès'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la mise à jour des informations',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── CONTRATS
    public function contrats(Professor $professor): JsonResponse
    {
        try {
            $contrats = $professor->contrats()
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                $contrats,
                'Contrats récupérés avec succès'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des contrats',
                500,
                $e->getMessage()
            );
        }
    }

    // ───────────────────────── STATISTIQUES
    public function stat(Professor $professor): JsonResponse
    {
        try {
            $stats = [
                'total_contrats'     => $professor->contrats()->count(),
                'active_contrats'    => $professor->contrats()->where('status', 'ongoing')->count(),
                'completed_contrats' => $professor->contrats()->where('status', 'completed')->count(),
                'total_amount'       => $professor->contrats()->sum('amount'),
            ];

            return $this->successResponse(
                $stats,
                'Statistiques récupérées avec succès'

            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des statistiques',
                500,
                $e->getMessage()
            );
        }
    }
}

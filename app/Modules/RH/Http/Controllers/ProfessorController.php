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

class ProfessorController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected ProfessorService $professorService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Liste des professeurs avec recherche et filtres
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'grade_id', 'bank', 'sort_by', 'sort_order']);
        $perPage = $this->getPerPage($request);
        
        $professors = $this->professorService->getAll($filters, $perPage);

        $professors->setCollection(
            ProfessorResource::collection($professors->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $professors,
            'Professeurs récupérés avec succès'
        );
    }

    /**
     * Créer un nouveau professeur
     */
    public function store(CreateProfessorRequest $request): JsonResponse
    {
        $data = $request->except(['rib', 'ifu']);
        $ribFile = $request->file('rib');
        $ifuFile = $request->file('ifu');

        $professor = $this->professorService->create(
            $data,
            auth()->id(),
            $ribFile,
            $ifuFile
        );

        return $this->createdResponse(
            new ProfessorResource($professor->load('grade')),
            'Professeur créé avec succès'
        );
    }

    /**
     * Afficher un professeur
     */
    public function show(Professor $professor): JsonResponse
    {
        return $this->successResponse(
            new ProfessorResource($professor->load('grade')),
            'Professeur récupéré avec succès'
        );
    }

    /**
     * Mettre à jour un professeur
     */
    public function update(UpdateProfessorRequest $request, Professor $professor): JsonResponse
    {
        $data = $request->except(['rib', 'ifu']);
        $ribFile = $request->file('rib');
        $ifuFile = $request->file('ifu');

        $professor = $this->professorService->update(
            $professor,
            $data,
            auth()->id(),
            $ribFile,
            $ifuFile
        );

        return $this->updatedResponse(
            new ProfessorResource($professor),
            'Professeur mis à jour avec succès'
        );
    }

    /**
     * Supprimer un professeur
     */
    public function destroy(Professor $professor): JsonResponse
    {
        $this->professorService->delete($professor);
        return $this->deletedResponse('Professeur supprimé avec succès');
    }

    /**
     * Récupérer la liste des banques utilisées
     */
    public function getBanks(): JsonResponse
    {
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
    }
}

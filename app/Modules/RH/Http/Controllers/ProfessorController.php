<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Professor;
use App\Modules\RH\Models\Contrat;
use App\Modules\RH\Http\Requests\CreateProfessorRequest;
use App\Modules\RH\Http\Requests\UpdateProfessorRequest;
use App\Modules\RH\Http\Resources\ProfessorResource;
use App\Modules\RH\Services\ProfessorService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfessorController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected ProfessorService $professorService
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'statut', 'grade_id', 'bank',
            'sort_by', 'sort_order',
            'nationality', 'city',
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
    }

    public function store(CreateProfessorRequest $request): JsonResponse
    {
        $data = $request->except(['rib', 'ifu']);
        $ribFile = $request->file('rib');
        $ifuFile = $request->file('ifu');

        $professor = $this->professorService->create(
            $data,
            Auth::id(),
            $ribFile,
            $ifuFile
        );

        return $this->createdResponse(
            new ProfessorResource($professor->load('grade')),
            'Professeur créé avec succès'
        );
    }

    public function show(Professor $professor): JsonResponse
    {
        return $this->successResponse(
            new ProfessorResource($professor->load('grade')),
            'Professeur récupéré avec succès'
        );
    }

    public function update(UpdateProfessorRequest $request, Professor $professor): JsonResponse
    {
        $data = $request->except(['rib', 'ifu']);
        $ribFile = $request->file('rib');
        $ifuFile = $request->file('ifu');

        $professor = $this->professorService->update(
            $professor,
            $data,
            Auth::id(),
            $ribFile,
            $ifuFile
        );

        return $this->updatedResponse(
            new ProfessorResource($professor),
            'Professeur mis à jour avec succès'
        );
    }

    public function destroy(Professor $professor): JsonResponse
    {
        $this->professorService->delete($professor);

        return $this->deletedResponse('Professeur supprimé avec succès');
    }

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

    public function updateAddress(Request $request, Professor $professor): JsonResponse
    {
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
            'Adresse mise à jour avec succès'
        );
    }

    public function contrats(Professor $professor): JsonResponse
    {
        $contrats = Contrat::where('professor_id', $professor->getKey())
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            $contrats,
            'Contrats récupérés avec succès'
        );
    }

    public function stats(Professor $professor): JsonResponse
    {
        $professorId = $professor->getKey();

        $stats = [
            'total_contrats'     => Contrat::where('professor_id', $professorId)->count(),
            'active_contrats'    => Contrat::where('professor_id', $professorId)
                                            ->where('statut', 'ongoing')->count(),
            'completed_contrats' => Contrat::where('professor_id', $professorId)
                                            ->where('statut', 'completed')->count(),
            'total_amount'       => Contrat::where('professor_id', $professorId)
                                            ->sum('amount'),
        ];

        return $this->successResponse(
            $stats,
            'Statistiques récupérées avec succès'
        );
    }
}
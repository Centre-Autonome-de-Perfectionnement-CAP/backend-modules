<?php

namespace App\Modules\Alumni\Http\Controllers;

use App\Modules\Alumni\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class AlumniController extends Controller
{
    public function __construct(private AlumniService $alumniService)
    {
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  PUBLIC
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Soumettre une fiche alumni (route publique).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ecole'                     => 'sometimes|in:CAP,EPAC',
            'nom'                       => 'required|string|max:255',
            'prenom'                    => 'required|string|max:255',
            'civilite'                  => 'required|in:Monsieur,Madame',
            'mail'                      => 'required|email|max:255|unique:alumni,mail',
            'telephone'                 => 'required|string|max:30',
            'situation_professionnelle' => 'required|string',
            'autre_situation'           => 'nullable|string|max:255',
            'secteur_emploi'            => 'required|string',
            'secteur_professionnel'     => 'required|string',
            'type_emploi'               => 'required|in:Employeur,Employe,Aucun',
            'nom_entreprise'            => 'nullable|string|max:255',
            'annee_entree'              => 'required|digits:4',
            'annee_sortie'              => 'required|digits:4',
            'promotion'                 => 'required|integer|min:1|max:999',
            'formation'                 => 'required|string',
            'autre_formation'           => 'nullable|string|max:255',
        ]);

        $alumni = $this->alumniService->submit($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vos informations ont été enregistrées avec succès. Bienvenue dans la communauté Alumni CAP-EPAC !',
            'data'    => ['id' => $alumni->id],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ADMIN — LISTE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Liste paginée des alumni (admin).
     *
     * Query params : ecole, formation, annee_sortie, promotion,
     *                situation_professionnelle, type_emploi,
     *                secteur_emploi, search, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);
        $filters = $request->only([
            'ecole', 'formation', 'annee_sortie', 'promotion',
            'situation_professionnelle', 'type_emploi', 'secteur_emploi', 'search',
        ]);

        $alumni = $this->alumniService->getAll($filters, $perPage);

        return response()->json([
            'success' => true,
            'data'    => $alumni->items(),
            'meta'    => [
                'total'        => $alumni->total(),
                'per_page'     => $alumni->perPage(),
                'current_page' => $alumni->currentPage(),
                'last_page'    => $alumni->lastPage(),
                'from'         => $alumni->firstItem(),
                'to'           => $alumni->lastItem(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ADMIN — DÉTAIL
    // ──────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $alumni = $this->alumniService->getById($id);

        return response()->json([
            'success' => true,
            'data'    => $alumni,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ADMIN — MISE À JOUR
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'ecole'                     => 'sometimes|in:CAP,EPAC',
            'nom'                       => 'sometimes|string|max:255',
            'prenom'                    => 'sometimes|string|max:255',
            'civilite'                  => 'sometimes|in:Monsieur,Madame',
            'mail'                      => 'sometimes|email|max:255|unique:alumni,mail,' . $id,
            'telephone'                 => 'sometimes|string|max:30',
            'situation_professionnelle' => 'sometimes|string',
            'autre_situation'           => 'nullable|string|max:255',
            'secteur_emploi'            => 'sometimes|string',
            'secteur_professionnel'     => 'sometimes|string',
            'type_emploi'               => 'sometimes|in:Employeur,Employe,Aucun',
            'nom_entreprise'            => 'nullable|string|max:255',
            'annee_entree'              => 'sometimes|digits:4',
            'annee_sortie'              => 'sometimes|digits:4',
            'promotion'                 => 'sometimes|integer|min:1|max:999',
            'formation'                 => 'sometimes|string',
            'autre_formation'           => 'nullable|string|max:255',
        ]);

        $alumni = $this->alumniService->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Fiche alumni mise à jour avec succès.',
            'data'    => $alumni,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ADMIN — SUPPRESSION
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $this->alumniService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Fiche alumni supprimée.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ADMIN — DASHBOARD KPI
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Dashboard avec tous les indicateurs KPI.
     *
     * Query params : ecole, annee_sortie
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $request->only(['ecole', 'annee_sortie']);
        $stats   = $this->alumniService->getDashboardStats($filters);

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }
}

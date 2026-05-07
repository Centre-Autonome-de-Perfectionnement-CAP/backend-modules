<?php

namespace App\Modules\Alumni\Http\Controllers;

use App\Modules\Alumni\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AlumniController extends Controller
{
    public function __construct(private AlumniService $alumniService)
    {
    }

    /**
     * Soumettre une fiche alumni (public).
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

    /**
     * Liste des alumni (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = $this->alumniService->getAll($request->only(['ecole', 'formation', 'annee_sortie', 'search']));

        return response()->json([
            'success' => true,
            'data'    => $alumni,
            'total'   => $alumni->count(),
        ]);
    }
}

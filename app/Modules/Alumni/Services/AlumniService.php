<?php

namespace App\Modules\Alumni\Services;

use App\Modules\Alumni\Models\Alumni;
use Illuminate\Support\Collection;

class AlumniService
{
    /**
     * Enregistre une nouvelle fiche alumni.
     */
    public function submit(array $data): Alumni
    {
        $formation    = $data['formation'] === 'Autre' ? null : $data['formation'];
        $autreFormation = $data['formation'] === 'Autre' ? ($data['autre_formation'] ?? null) : null;

        $situation    = $data['situation_professionnelle'] === 'Autre' ? 'Autre' : $data['situation_professionnelle'];
        $autreSit     = $data['situation_professionnelle'] === 'Autre' ? ($data['autre_situation'] ?? null) : null;

        $nomEntreprise = in_array($data['type_emploi'], ['Employe', 'Employeur'])
            ? ($data['nom_entreprise'] ?? null)
            : null;

        return Alumni::create([
            'ecole'                    => $data['ecole'] ?? 'CAP',
            'nom'                      => $data['nom'],
            'prenom'                   => $data['prenom'],
            'civilite'                 => $data['civilite'],
            'mail'                     => $data['mail'],
            'telephone'                => $data['telephone'],
            'situation_professionnelle' => $situation,
            'autre_situation'          => $autreSit,
            'secteur_emploi'           => $data['secteur_emploi'],
            'secteur_professionnel'    => $data['secteur_professionnel'],
            'type_emploi'              => $data['type_emploi'],
            'nom_entreprise'           => $nomEntreprise,
            'annee_entree'             => $data['annee_entree'],
            'annee_sortie'             => $data['annee_sortie'],
            'promotion'                => $data['promotion'],
            'formation'                => $formation ?? ($data['autre_formation'] ?? ''),
            'autre_formation'          => $autreFormation,
        ]);
    }

    /**
     * Retourne la liste des alumni avec filtres optionnels (admin).
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Alumni::query()->latest();

        if (!empty($filters['ecole'])) {
            $query->where('ecole', $filters['ecole']);
        }
        if (!empty($filters['formation'])) {
            $query->where('formation', $filters['formation']);
        }
        if (!empty($filters['annee_sortie'])) {
            $query->where('annee_sortie', $filters['annee_sortie']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                  ->orWhere('prenom', 'like', $search)
                  ->orWhere('mail', 'like', $search);
            });
        }

        return $query->get();
    }
}

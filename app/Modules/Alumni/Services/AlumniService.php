<?php

namespace App\Modules\Alumni\Services;

use App\Modules\Alumni\Models\Alumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlumniService
{
    // ──────────────────────────────────────────────────────────────────────────
    //  SOUMISSION PUBLIQUE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Enregistre une nouvelle fiche alumni.
     */
    public function submit(array $data): Alumni
    {
        $formation     = $data['formation'] === 'Autre' ? null : $data['formation'];
        $autreFormation = $data['formation'] === 'Autre' ? ($data['autre_formation'] ?? null) : null;

        $situation = $data['situation_professionnelle'] === 'Autre'
            ? 'Autre'
            : $data['situation_professionnelle'];
        $autreSit  = $data['situation_professionnelle'] === 'Autre'
            ? ($data['autre_situation'] ?? null)
            : null;

        $nomEntreprise = in_array($data['type_emploi'], ['Employe', 'Employeur'])
            ? ($data['nom_entreprise'] ?? null)
            : null;

        return Alumni::create([
            'ecole'                     => $data['ecole'] ?? 'CAP',
            'nom'                       => $data['nom'],
            'prenom'                    => $data['prenom'],
            'civilite'                  => $data['civilite'],
            'mail'                      => $data['mail'],
            'telephone'                 => $data['telephone'],
            'situation_professionnelle' => $situation,
            'autre_situation'           => $autreSit,
            'secteur_emploi'            => $data['secteur_emploi'],
            'secteur_professionnel'     => $data['secteur_professionnel'],
            'type_emploi'               => $data['type_emploi'],
            'nom_entreprise'            => $nomEntreprise,
            'annee_entree'              => $data['annee_entree'],
            'annee_sortie'              => $data['annee_sortie'],
            'promotion'                 => $data['promotion'],
            'formation'                 => $formation ?? ($data['autre_formation'] ?? ''),
            'autre_formation'           => $autreFormation,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  LISTE PAGINÉE (admin)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retourne la liste paginée des alumni avec filtres.
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Alumni::query()->latest();

        if (!empty($filters['ecole'])) {
            $query->where('ecole', $filters['ecole']);
        }
        if (!empty($filters['formation'])) {
            $query->where('formation', 'like', '%' . $filters['formation'] . '%');
        }
        if (!empty($filters['annee_sortie'])) {
            $query->where('annee_sortie', $filters['annee_sortie']);
        }
        if (!empty($filters['promotion'])) {
            $query->where('promotion', $filters['promotion']);
        }
        if (!empty($filters['situation_professionnelle'])) {
            $query->where('situation_professionnelle', $filters['situation_professionnelle']);
        }
        if (!empty($filters['type_emploi'])) {
            $query->where('type_emploi', $filters['type_emploi']);
        }
        if (!empty($filters['secteur_emploi'])) {
            $query->where('secteur_emploi', 'like', '%' . $filters['secteur_emploi'] . '%');
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                  ->orWhere('prenom', 'like', $search)
                  ->orWhere('mail', 'like', $search)
                  ->orWhere('telephone', 'like', $search)
                  ->orWhere('nom_entreprise', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function getById(int $id): Alumni
    {
        return Alumni::findOrFail($id);
    }

    public function update(int $id, array $data): Alumni
    {
        $alumni = Alumni::findOrFail($id);
        $alumni->update($data);
        return $alumni->fresh();
    }

    public function delete(int $id): void
    {
        Alumni::findOrFail($id)->delete();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  DASHBOARD / KPI
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calcule tous les indicateurs KPI pour le dashboard Alumni.
     */
    public function getDashboardStats(array $filters = []): array
    {
        $base = Alumni::query();

        if (!empty($filters['ecole'])) {
            $base->where('ecole', $filters['ecole']);
        }
        if (!empty($filters['annee_sortie'])) {
            $base->where('annee_sortie', $filters['annee_sortie']);
        }

        $total = (clone $base)->count();

        // ── Totaux rapides ─────────────────────────────────────────────────────
        $totalCap  = (clone $base)->where('ecole', 'CAP')->count();
        $totalEpac = (clone $base)->where('ecole', 'EPAC')->count();

        $totalInserted = (clone $base)
            ->whereIn('type_emploi', ['Employe', 'Employeur'])
            ->count();

        $tauxInsertion = $total > 0
            ? round(($totalInserted / $total) * 100, 1)
            : 0;

        // ── Répartition par école ──────────────────────────────────────────────
        $parEcole = (clone $base)
            ->select('ecole', DB::raw('count(*) as total'))
            ->groupBy('ecole')
            ->pluck('total', 'ecole')
            ->toArray();

        // ── Répartition par type d'emploi ─────────────────────────────────────
        $parTypeEmploi = (clone $base)
            ->select('type_emploi', DB::raw('count(*) as total'))
            ->groupBy('type_emploi')
            ->orderByDesc('total')
            ->pluck('total', 'type_emploi')
            ->toArray();

        // ── Répartition par situation professionnelle ──────────────────────────
        $parSituation = (clone $base)
            ->select('situation_professionnelle', DB::raw('count(*) as total'))
            ->groupBy('situation_professionnelle')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'situation' => $r->situation_professionnelle,
                'total'     => $r->total,
            ])
            ->values()
            ->toArray();

        // ── Top 10 formations ──────────────────────────────────────────────────
        $topFormations = (clone $base)
            ->select('formation', DB::raw('count(*) as total'))
            ->whereNotNull('formation')
            ->where('formation', '!=', '')
            ->groupBy('formation')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'formation' => $r->formation,
                'total'     => $r->total,
            ])
            ->values()
            ->toArray();

        // ── Top 10 secteurs d'emploi ──────────────────────────────────────────
        $topSecteurs = (clone $base)
            ->select('secteur_emploi', DB::raw('count(*) as total'))
            ->whereNotNull('secteur_emploi')
            ->groupBy('secteur_emploi')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'secteur' => $r->secteur_emploi,
                'total'   => $r->total,
            ])
            ->values()
            ->toArray();

        // ── Top 10 secteurs professionnels ────────────────────────────────────
        $topSecteursProf = (clone $base)
            ->select('secteur_professionnel', DB::raw('count(*) as total'))
            ->whereNotNull('secteur_professionnel')
            ->groupBy('secteur_professionnel')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'secteur' => $r->secteur_professionnel,
                'total'   => $r->total,
            ])
            ->values()
            ->toArray();

        // ── Évolution par année de sortie ─────────────────────────────────────
        $parAnneeSortie = (clone $base)
            ->select('annee_sortie', DB::raw('count(*) as total'))
            ->groupBy('annee_sortie')
            ->orderBy('annee_sortie')
            ->get()
            ->map(fn ($r) => [
                'annee' => $r->annee_sortie,
                'total' => $r->total,
            ])
            ->values()
            ->toArray();

        // ── Inscriptions récentes (30 derniers jours) ─────────────────────────
        $recents = (clone $base)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // ── Promotions représentées ───────────────────────────────────────────
        $promotions = (clone $base)
            ->select('promotion', DB::raw('count(*) as total'))
            ->groupBy('promotion')
            ->orderBy('promotion')
            ->pluck('total', 'promotion')
            ->toArray();

        // ── Répartition par civilité ──────────────────────────────────────────
        $parCivilite = (clone $base)
            ->select('civilite', DB::raw('count(*) as total'))
            ->groupBy('civilite')
            ->pluck('total', 'civilite')
            ->toArray();

        return [
            'totaux' => [
                'total'           => $total,
                'cap'             => $totalCap,
                'epac'            => $totalEpac,
                'inseres'         => $totalInserted,
                'taux_insertion'  => $tauxInsertion,
                'recents_30j'     => $recents,
            ],
            'par_ecole'              => $parEcole,
            'par_type_emploi'        => $parTypeEmploi,
            'par_situation'          => $parSituation,
            'par_civilite'           => $parCivilite,
            'top_formations'         => $topFormations,
            'top_secteurs_emploi'    => $topSecteurs,
            'top_secteurs_prof'      => $topSecteursProf,
            'par_annee_sortie'       => $parAnneeSortie,
            'promotions'             => $promotions,
        ];
    }
}

<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;

/**
 * Toutes les lectures DB pour les demandes (index, show, stats).
 * Aucune écriture ici.
 *
 * CORRECTIF (16/08/2026 — résolution merge Benoite) :
 *   Le schéma réel de document_requests (migration 2026_06_10_...) ne
 *   contient PAS de colonnes chef_division_comment / secretaire_comment /
 *   comptable_comment / *_reviewed_at directement sur la table — ces
 *   informations sont dérivées de document_request_histories via les
 *   sous-requêtes corrélées ci-dessous (historySubqueries()), qui
 *   reconstituent le dernier commentaire/horodatage de chaque rôle à
 *   partir du journal d'audit. C'est la bonne approche : elle ne dépend
 *   d'aucune colonne fantôme.
 *
 *   BASE_COLUMNS ne liste donc que les colonnes qui existent RÉELLEMENT
 *   sur document_requests (vérifié contre la migration réelle).
 *
 *   `chef_division_type` n'est PAS une colonne de document_requests —
 *   c'est une colonne de `users` (accédée via $user->chef_division_type
 *   dans listing()), à ne jamais mettre dans BASE_COLUMNS.
 *
 * B2.2 — Index nécessaires pour rester performant (voir migration
 * 2026_07_01_000001_add_missing_indexes_to_document_requests.php) :
 *   - (student_pending_student_id, type, status)
 *   - (status, created_at)
 *   - (responsable_division_type, status)
 *   - (is_in_correction_circuit, status)
 */
class DocumentRequestQueryService
{
    // ── Colonnes réelles de document_requests pour le listing ─────────────────

    private const BASE_COLUMNS = [
        'dr.id',
        'dr.reference',
        'dr.type',
        'dr.status',
        'dr.has_flag',
        'dr.email',
        'dr.files',
        'dr.complement_files',
        'dr.secretary_files',
        'dr.complement_at',
        'dr.submitted_at',
        'dr.created_at',
        'dr.updated_at',
        'dr.delivered_at',
        'dr.rejected_reason',
        'dr.rejected_by',
        'dr.signature_type',
        'dr.department_name',
        'dr.responsable_division_type',
        'dr.is_in_correction_circuit',
        'dr.correction_origin_role',
        'dr.correction_origin_status',
        'dr.student_pending_student_id',
        'pi.last_name',
        'pi.first_names',
        'dept.name as department',
        'ay.academic_year',
    ];

    /**
     * Sous-requêtes corrélées qui reconstituent les commentaires et
     * horodatages par rôle à partir de document_request_histories
     * (ces informations n'existent pas en colonnes directes sur
     * document_requests). Chacune utilise l'index
     * drh_request_role_idx (dr_id, actor_role, created_at).
     *
     * Pattern : on récupère la dernière entrée pour chaque rôle,
     * ce qui correspond toujours à l'action la plus récente de ce rôle.
     *
     * Méthode (et non constante) car le rôle "Responsable Division" peut être
     * enregistré sous deux graphies différentes dans actor_role
     * ('responsable-division' ou l'ancien alias 'chef-division') selon
     * l'historique de la base — on doit chercher les deux.
     */
    private function historySubqueries(): array
    {
        $divisionRoles = $this->sqlInClause(
            WorkflowConstants::roleSlugVariants('responsable-division')
        );

        return [
            // Commentaires — dernière entrée avec comment non null pour chaque rôle
            "COALESCE((
                SELECT h.comment FROM document_request_histories h
                WHERE h.document_request_id = dr.id
                  AND h.actor_role = 'secretaire'
                  AND h.comment IS NOT NULL
                ORDER BY h.created_at DESC LIMIT 1
            ), NULL) AS secretaire_comment",

            "COALESCE((
                SELECT h.comment FROM document_request_histories h
                WHERE h.document_request_id = dr.id
                  AND h.actor_role = 'comptable'
                  AND h.comment IS NOT NULL
                ORDER BY h.created_at DESC LIMIT 1
            ), NULL) AS comptable_comment",

            "COALESCE((
                SELECT h.comment FROM document_request_histories h
                WHERE h.document_request_id = dr.id
                  AND h.actor_role IN ({$divisionRoles})
                  AND h.comment IS NOT NULL
                ORDER BY h.created_at DESC LIMIT 1
            ), NULL) AS responsable_division_comment",

            // Horodatages — dernière action de chaque rôle
            "(SELECT h.created_at FROM document_request_histories h
              WHERE h.document_request_id = dr.id
                AND h.actor_role = 'comptable'
              ORDER BY h.created_at DESC LIMIT 1
            ) AS comptable_reviewed_at",

            "(SELECT h.created_at FROM document_request_histories h
              WHERE h.document_request_id = dr.id
                AND h.actor_role IN ({$divisionRoles})
              ORDER BY h.created_at DESC LIMIT 1
            ) AS responsable_division_reviewed_at",

            "(SELECT h.created_at FROM document_request_histories h
              WHERE h.document_request_id = dr.id
                AND h.actor_role = 'chef-cap'
              ORDER BY h.created_at DESC LIMIT 1
            ) AS chef_cap_reviewed_at",

            "(SELECT h.created_at FROM document_request_histories h
              WHERE h.document_request_id = dr.id
                AND h.actor_role = 'sec-da'
              ORDER BY h.created_at DESC LIMIT 1
            ) AS sec_da_reviewed_at",

            "(SELECT h.created_at FROM document_request_histories h
              WHERE h.document_request_id = dr.id
                AND h.actor_role = 'directrice-adjointe'
              ORDER BY h.created_at DESC LIMIT 1
            ) AS directrice_adjointe_reviewed_at",

            "(SELECT h.created_at FROM document_request_histories h
              WHERE h.document_request_id = dr.id
                AND h.actor_role = 'sec-dir'
              ORDER BY h.created_at DESC LIMIT 1
            ) AS sec_directeur_reviewed_at",
        ];
    }

    /**
     * Construit une clause IN(...) sûre à partir d'une liste fixe et interne
     * de slugs de rôles (jamais d'entrée utilisateur) — pas de risque
     * d'injection, ces valeurs viennent uniquement de WorkflowConstants.
     */
    private function sqlInClause(array $values): string
    {
        return "'" . implode("','", array_map(
            fn($v) => str_replace("'", "''", $v),
            $values
        )) . "'";
    }

    private const MATRICULE_SUBQUERY = "
        (SELECT student_id_number FROM students s
         JOIN student_pending_student sps2 ON sps2.student_id = s.id
         WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule
    ";

    // ── Base query ────────────────────────────────────────────────────────────

    private function baseQuery()
    {
        return DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->leftJoin('departments as dept', 'ps.department_id', '=', 'dept.id')
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id');
    }

    /**
     * Construit le tableau de select pour le listing :
     * colonnes directes + sous-requêtes histories + matricule.
     */
    private function buildListingSelect(): array
    {
        return array_merge(
            self::BASE_COLUMNS,
            array_map(fn($sq) => DB::raw($sq), $this->historySubqueries()),
            [DB::raw(self::MATRICULE_SUBQUERY)]
        );
    }

    // ── Listing ────────────────────────────────────────────────────────────────

    public function listing(string $role, $user, array $filters = []): \Illuminate\Support\Collection
    {
        // Le rôle peut arriver sous l'une ou l'autre graphie (responsable-division /
        // chef-division) selon ce qui est stocké en base pour cet utilisateur :
        // on normalise systématiquement vers le slug canonique avant toute
        // comparaison ou lookup dans WorkflowConstants.
        $role = WorkflowConstants::canonicalRole($role);

        $query = $this->baseQuery()->select($this->buildListingSelect());

        // Filtrage par rôle
        $visibleStatuses = WorkflowConstants::VISIBLE_STATUSES[$role] ?? ['pending'];
        if (!empty($visibleStatuses)) {
            $query->whereIn('dr.status', $visibleStatuses);
        }

        // Responsable Division : filtrer par son type (utilise l'index dr_responsable_division_type_idx)
        // La colonne sur l'utilisateur s'appelle chef_division_type (nom historique en BD, table users).
        // La colonne sur le dossier s'appelle responsable_division_type.
        if ($role === 'responsable-division' && $user->chef_division_type) {
            $query->where('dr.responsable_division_type', $user->chef_division_type);
        }

        // Filtres utilisateur
        if (!empty($filters['status'])) {
            $query->where('dr.status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query->where('dr.type', $filters['type']);
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($s) {
                $q->where('pi.last_name', 'like', $s)
                  ->orWhere('pi.first_names', 'like', $s)
                  ->orWhere('dr.reference', 'like', $s);
            });
        }

        // Tri par ordre d'arrivée chronologique (du plus ancien en haut au plus nouveau en bas)
        return $query->orderBy('dr.created_at', 'asc')->get();
    }

    // ── Listing paginé ───────────────────────────────────────────────────────
    //
    // Reproduit exactement la même construction de requête que listing()
    // ci-dessus (même colonnes, même filtrage par rôle, mêmes filtres
    // utilisateur), avec paginate() au lieu de get(). Utilisée par le
    // nouvel endpoint GET /document-requests/paginated, qui coexiste
    // avec l'ancien endpoint non paginé pour ne rien casser côté frontend.

    public function paginatedListing(string $role, $user, array $filters = [], int $perPage = 25): \Illuminate\Pagination\LengthAwarePaginator
    {
        $role = WorkflowConstants::canonicalRole($role);

        $query = $this->baseQuery()->select($this->buildListingSelect());

        $visibleStatuses = WorkflowConstants::VISIBLE_STATUSES[$role] ?? ['pending'];
        if (!empty($visibleStatuses)) {
            $query->whereIn('dr.status', $visibleStatuses);
        }

        if ($role === 'responsable-division' && $user->chef_division_type) {
            $query->where('dr.responsable_division_type', $user->chef_division_type);
        }

        if (!empty($filters['status'])) {
            $query->where('dr.status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query->where('dr.type', $filters['type']);
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($s) {
                $q->where('pi.last_name', 'like', $s)
                  ->orWhere('pi.first_names', 'like', $s)
                  ->orWhere('dr.reference', 'like', $s);
            });
        }

        return $query->orderBy('dr.created_at', 'asc')->paginate($perPage);
    }

    // ── Détail ────────────────────────────────────────────────────────────────

    public function findOrFail(int $id): object
    {
        // Pour le détail on prend dr.* (toutes les colonnes réelles restantes)
        // + infos étudiant + sous-requêtes histories.
        //
        // CORRECTIF : $select était construit puis jamais utilisé dans
        // l'ancienne version (la requête réelle appelait un ->select()
        // différent, plus simple, sans les sous-requêtes) — la vue détail
        // n'affichait donc jamais les commentaires/horodatages par rôle.
        // Corrigé : $select est maintenant bien celui utilisé ci-dessous.
        $select = array_merge(
            ['dr.*', 'pi.birth_date', 'dept.name as department', 'ay.academic_year'],
            array_map(fn($sq) => DB::raw($sq), $this->historySubqueries()),
            [
                DB::raw(self::MATRICULE_SUBQUERY),
                DB::raw('ps.level as study_level'),
            ]
        );

        $demande = $this->baseQuery()
            ->where('dr.id', $id)
            ->select($select)
            ->first();

        if (!$demande) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Demande #{$id} introuvable.");
        }

        return $demande;
    }

    // ── Stats direction ───────────────────────────────────────────────────────

    public function statsForDirectionUser(int $userId, string $role): array
    {
        $myStatus = array_flip(WorkflowConstants::STATUS_TO_ROLE)[$role] ?? null;

        $totalInProgress = DB::table('document_requests')
            ->whereNotIn('status', ['picked_up', 'rejected'])
            ->count();

        $pendingAtMyLevel = $myStatus
            ? DB::table('document_requests')->where('status', $myStatus)->count()
            : 0;

        $totalValidated = DB::table('document_request_histories')
            ->where('actor_id', $userId)
            ->whereIn('action_type', ['validation', 'validation_flagged'])
            ->count();

        $totalRejected = DB::table('document_request_histories')
            ->where('actor_id', $userId)
            ->where('action_type', 'rejection')
            ->count();

        return compact('totalInProgress', 'pendingAtMyLevel', 'totalValidated', 'totalRejected');
    }
}

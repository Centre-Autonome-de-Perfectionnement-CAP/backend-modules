<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;

/**
 * Toutes les lectures DB pour les demandes (index, show, stats).
 * Aucune écriture ici.
 *
 * ─── STRATÉGIE POST-NETTOYAGE ────────────────────────────────────────────────
 *
 * Les colonnes supprimées de document_requests (commentaires par rôle,
 * horodatages de révision, processed_by_*) sont reconstituées via des
 * sous-requêtes corrélées sur document_request_histories.
 *
 * Ces sous-requêtes sont ultra-rapides grâce à l'index composite :
 *   drh_request_role_idx (document_request_id, actor_role, created_at)
 *
 * ─── DEUX MODES DE SÉLECTION ─────────────────────────────────────────────────
 *
 * listing()    → BASE_SELECT : colonnes légères + sous-requêtes commentaires/timestamps
 *                utiles pour l'affichage en liste (pas de dr.*)
 *
 * findOrFail() → DETAIL_SELECT : dr.* + sous-requêtes pour la vue détail
 *                (inclut complement_files, files, etc.)
 */
class DocumentRequestQueryService
{
    // ── Colonnes directes pour le listing ─────────────────────────────────────

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
        'dr.delivered_at',
        'dr.updated_at',
        'dr.rejected_reason',
        'dr.rejected_by',
        'dr.signature_type',
        'dr.department_name',
        'dr.chef_division_type',
        'dr.is_in_correction_circuit',
        'dr.correction_origin_role',
        'dr.correction_origin_status',
        'dr.student_pending_student_id',
        // Étudiant
        'pi.last_name',
        'pi.first_names',
        'dept.name as department',
        'ay.academic_year',
    ];

    /**
     * Sous-requêtes corrélées qui remplacent les colonnes supprimées.
     * Chacune utilise l'index drh_request_role_idx (dr_id, actor_role, created_at).
     *
     * Pattern : on récupère la dernière entrée pour chaque rôle,
     * ce qui correspond toujours à l'action la plus récente de ce rôle.
     */
    private const HISTORY_SUBQUERIES = [
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
              AND h.actor_role = 'chef-division'
              AND h.comment IS NOT NULL
            ORDER BY h.created_at DESC LIMIT 1
        ), NULL) AS chef_division_comment",

        // Horodatages — dernière action de chaque rôle
        "(SELECT h.created_at FROM document_request_histories h
          WHERE h.document_request_id = dr.id
            AND h.actor_role = 'comptable'
          ORDER BY h.created_at DESC LIMIT 1
        ) AS comptable_reviewed_at",

        "(SELECT h.created_at FROM document_request_histories h
          WHERE h.document_request_id = dr.id
            AND h.actor_role = 'chef-division'
          ORDER BY h.created_at DESC LIMIT 1
        ) AS chef_division_reviewed_at",

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

    private const MATRICULE_SUBQUERY = "
        (SELECT s.student_id_number FROM students s
         JOIN student_pending_student sps2 ON sps2.student_id = s.id
         WHERE sps2.id = dr.student_pending_student_id LIMIT 1) AS matricule
    ";

    // ── Base query ─────────────────────────────────────────────────────────────

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
            array_map(fn($sq) => DB::raw($sq), self::HISTORY_SUBQUERIES),
            [DB::raw(self::MATRICULE_SUBQUERY)]
        );
    }

    // ── Listing ────────────────────────────────────────────────────────────────

    public function listing(string $role, $user, array $filters = []): \Illuminate\Support\Collection
    {
        $query = $this->baseQuery()->select($this->buildListingSelect());

        // Filtrage par rôle
        $visibleStatuses = WorkflowConstants::VISIBLE_STATUSES[$role] ?? ['pending'];
        if (!empty($visibleStatuses)) {
            $query->whereIn('dr.status', $visibleStatuses);
        }

        // Responsable Division : filtrer par son type (utilise l'index dr_chef_division_type_idx)
        if ($role === 'chef-division' && $user->chef_division_type) {
            $query->where('dr.chef_division_type', $user->chef_division_type);
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

    // ── Détail ─────────────────────────────────────────────────────────────────

    public function findOrFail(int $id): object
    {
        // Pour le détail on prend dr.* (toutes les colonnes restantes)
        // + infos étudiant + sous-requêtes histories
        $select = array_merge(
            ['dr.*', 'pi.birth_date', 'dept.name as department', 'ay.academic_year'],
            array_map(fn($sq) => DB::raw($sq), self::HISTORY_SUBQUERIES),
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

    // ── Stats direction ────────────────────────────────────────────────────────

    public function statsForDirectionUser(int $userId, string $role): array
    {
        $myStatus = array_flip(WorkflowConstants::STATUS_TO_ROLE)[$role] ?? null;

        $totalInProgress = DB::table('document_requests')
            ->whereNotIn('status', ['picked_up', 'rejected'])
            ->count();

        $pendingAtMyLevel = $myStatus
            ? DB::table('document_requests')->where('status', $myStatus)->count()
            : 0;

        // Utilise l'index drh_actor_action_idx (actor_id, action_type)
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

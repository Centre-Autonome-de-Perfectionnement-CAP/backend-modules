<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;

/**
 * Toutes les lectures DB pour les demandes (index, show, stats).
 * Aucune écriture ici.
 *
 * Harmonisation : ajout de dr.complement_files dans BASE_SELECT
 * pour que le progiciel puisse afficher les pièces complémentaires
 * via DossierFilesSplit.
 */
class DocumentRequestQueryService
{
    // ── Colonnes communes pour index et show ──────────────────────────────────

    private const BASE_SELECT = [
        'dr.id',
        'dr.reference',
        'dr.type',
        'dr.status',
        'dr.has_flag',
        'dr.email',
        'dr.files',
        'dr.complement_files',          // ← AJOUTÉ — pièces complémentaires
        'dr.submitted_at',
        'dr.updated_at',
        'dr.rejected_reason',
        'dr.rejected_by',
        'dr.chef_division_comment',
        'dr.secretaire_comment',
        'dr.comptable_comment',
        'dr.signature_type',
        'dr.department_name',
        'dr.chef_division_type',
        'dr.chef_division_reviewed_at',
        'dr.comptable_reviewed_at',
        'dr.chef_cap_reviewed_at',
        'dr.sec_da_reviewed_at',
        'dr.directrice_adjointe_reviewed_at',
        'dr.sec_directeur_reviewed_at',
        'dr.delivered_at',
        'dr.student_pending_student_id',
        'pi.last_name',
        'pi.first_names',
        'dept.name as department',
        'ay.academic_year',
    ];

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

    // ── Listing ───────────────────────────────────────────────────────────────

    public function listing(string $role, $user, array $filters = []): \Illuminate\Support\Collection
    {
        $query = $this->baseQuery()
            ->select(array_merge(self::BASE_SELECT, [DB::raw(self::MATRICULE_SUBQUERY)]));

        // Filtrage par rôle
        $visibleStatuses = WorkflowConstants::VISIBLE_STATUSES[$role] ?? ['pending'];
        if (!empty($visibleStatuses)) {
            $query->whereIn('dr.status', $visibleStatuses);
        }

        // Chef division : filtrer par son type
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

        return $query->orderBy('dr.updated_at', 'desc')->get();
    }

    // ── Détail ────────────────────────────────────────────────────────────────

    public function findOrFail(int $id): object
    {
        $demande = $this->baseQuery()
            ->where('dr.id', $id)
            ->select(array_merge(
                ['dr.*', 'pi.birth_date', 'dept.name as department', 'ay.academic_year'],
                [DB::raw(self::MATRICULE_SUBQUERY), DB::raw('ps.level as study_level')]
            ))
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
            ->whereNotIn('status', ['delivered', 'rejected'])
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

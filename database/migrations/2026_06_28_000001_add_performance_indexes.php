<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index de performance — idempotente (vérifie avant de créer, jamais d'erreur si déjà là).
 *
 * Gains ciblés par index :
 *
 * idx_dr_status
 *   → badge-count (COUNT WHERE status IN ...) et listing (WHERE status IN ...)
 *   → AVANT : full table scan à chaque requête
 *   → APRÈS : index seek, quasi-instantané
 *
 * idx_dr_status_created
 *   → listing trié : WHERE status IN (...) ORDER BY created_at ASC
 *   → couvre le filtre ET le tri en un seul index (covering index)
 *
 * idx_dr_status_resp_div
 *   → listing Responsable Division : WHERE status=? AND responsable_division_type=?
 *
 * idx_dr_sps_id
 *   → jointure document_requests → student_pending_student dans le listing
 *
 * idx_drh_role_date
 *   → 9 sous-requêtes corrélées dans historySubqueries() du QueryService
 *   → AVANT : 9 sous-requêtes × N lignes = jusqu'à 450 requêtes par listing de 50 demandes
 *   → APRÈS : index seek sur (document_request_id, actor_role, created_at DESC)
 *   → C'est l'index le plus impactant du lot
 *
 * idx_drh_dr_id
 *   → historique d'une demande : WHERE document_request_id = ?
 *
 * idx_sps_ps_id, idx_sps_s_id
 *   → jointures student_pending_student dans le listing
 *
 * idx_ps_pi_id, idx_ps_status
 *   → jointures pending_students dans le listing
 *
 * idx_ru_role_id, idx_ru_user_id
 *   → findUsersWithRole() dans NotificationService : appelée à chaque transition
 *
 * idx_users_deleted_at
 *   → WHERE deleted_at IS NULL dans findUsersWithRole()
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── document_requests ─────────────────────────────────────────────────
        Schema::table('document_requests', function (Blueprint $table) {
            $this->idx($table, 'document_requests', ['status'],                              'idx_dr_status');
            $this->idx($table, 'document_requests', ['status', 'created_at'],               'idx_dr_status_created');
            $this->idx($table, 'document_requests', ['status', 'responsable_division_type'],'idx_dr_status_resp_div');
            $this->idx($table, 'document_requests', ['student_pending_student_id'],          'idx_dr_sps_id');
            $this->idx($table, 'document_requests', ['type'],                               'idx_dr_type');
            $this->idx($table, 'document_requests', ['updated_at'],                         'idx_dr_updated_at');
        });

        // ── document_request_histories — LE PLUS IMPORTANT ───────────────────
        Schema::table('document_request_histories', function (Blueprint $table) {
            // Couvre les 9 sous-requêtes corrélées de historySubqueries() :
            // WHERE document_request_id = ? AND actor_role = ? ORDER BY created_at DESC LIMIT 1
            $this->idx(
                $table, 'document_request_histories',
                ['document_request_id', 'actor_role', 'created_at'],
                'idx_drh_role_date'
            );
            $this->idx($table, 'document_request_histories', ['document_request_id'], 'idx_drh_dr_id');
            $this->idx($table, 'document_request_histories', ['actor_id', 'action_type'], 'idx_drh_actor_action');
        });

        // ── Jointures student ─────────────────────────────────────────────────
        Schema::table('student_pending_student', function (Blueprint $table) {
            $this->idx($table, 'student_pending_student', ['pending_student_id'], 'idx_sps_ps_id');
            $this->idx($table, 'student_pending_student', ['student_id'],         'idx_sps_s_id');
        });

        Schema::table('pending_students', function (Blueprint $table) {
            $this->idx($table, 'pending_students', ['personal_information_id'], 'idx_ps_pi_id');
            $this->idx($table, 'pending_students', ['status'],                  'idx_ps_status');
        });

        // ── role_user — findUsersWithRole() à chaque transition ───────────────
        Schema::table('role_user', function (Blueprint $table) {
            $this->idx($table, 'role_user', ['role_id'], 'idx_ru_role_id');
            $this->idx($table, 'role_user', ['user_id'], 'idx_ru_user_id');
        });

        // ── users ─────────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $this->idx($table, 'users', ['deleted_at'], 'idx_users_deleted_at');
        });
    }

    public function down(): void
    {
        $map = [
            'document_requests'          => ['idx_dr_status','idx_dr_status_created','idx_dr_status_resp_div','idx_dr_sps_id','idx_dr_type','idx_dr_updated_at'],
            'document_request_histories' => ['idx_drh_role_date','idx_drh_dr_id','idx_drh_actor_action'],
            'student_pending_student'    => ['idx_sps_ps_id','idx_sps_s_id'],
            'pending_students'           => ['idx_ps_pi_id','idx_ps_status'],
            'role_user'                  => ['idx_ru_role_id','idx_ru_user_id'],
            'users'                      => ['idx_users_deleted_at'],
        ];

        foreach ($map as $tbl => $idxs) {
            Schema::table($tbl, function (Blueprint $t) use ($idxs) {
                foreach ($idxs as $i) {
                    try { $t->dropIndex($i); } catch (\Throwable) {}
                }
            });
        }
    }

    /**
     * Crée l'index seulement s'il n'existe pas déjà.
     * Permet de relancer la migration sans erreur.
     */
    private function idx(Blueprint $table, string $tbl, array $cols, string $name): void
    {
        $exists = DB::select("SHOW INDEX FROM `{$tbl}` WHERE Key_name = ?", [$name]);
        if (empty($exists)) {
            $table->index($cols, $name);
        }
    }
};

<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DocumentRequestController
 *
 * Responsabilité : lecture uniquement (index + show).
 *
 * Transitions  → DocumentRequestTransitionController
 * Historique   → DocumentRequestHistoryController
 */
class DocumentRequestController extends Controller
{
    use ApiResponse;

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $role = $user->roles->first()?->slug ?? null;

        $query = $this->baseQuery()->select($this->indexColumns());

        $visibleStatuses = $this->visibleStatusesFor($role);
        if (!empty($visibleStatuses)) {
            $query->whereIn('dr.status', $visibleStatuses);
        }

        if ($role === 'chef-division' && $user->chef_division_type) {
            $query->where('dr.chef_division_type', $user->chef_division_type);
        }

        if ($request->filled('status')) {
            $query->where('dr.status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('dr.type', $request->type);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('pi.last_name', 'like', $s)
                  ->orWhere('pi.first_names', 'like', $s)
                  ->orWhere('dr.reference', 'like', $s);
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('dr.updated_at', 'desc')->get(),
            'role'    => $role,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $demande = $this->baseQuery()
            ->where('dr.id', $id)
            ->select(array_merge($this->indexColumns(), [
                'pi.birth_date',
                DB::raw('ps.level as study_level'),
            ]))
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $demande]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVÉS
    // ─────────────────────────────────────────────────────────────────────────

    private function baseQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->leftJoin('departments as dept', 'ps.department_id', '=', 'dept.id')
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id');
    }

    private function indexColumns(): array
    {
        return [
            'dr.id', 'dr.reference', 'dr.type', 'dr.status',
            'dr.email', 'dr.files', 'dr.submitted_at', 'dr.updated_at',
            'dr.rejected_reason', 'dr.rejected_by',
            'dr.chef_division_comment', 'dr.secretaire_comment', 'dr.comptable_comment',
            'dr.signature_type', 'dr.department_name', 'dr.chef_division_type',
            'dr.chef_division_reviewed_at', 'dr.comptable_reviewed_at',
            'dr.chef_cap_reviewed_at', 'dr.sec_da_reviewed_at',
            'dr.directrice_adjointe_reviewed_at', 'dr.sec_directeur_reviewed_at',
            'dr.delivered_at', 'dr.student_pending_student_id',
            'pi.last_name', 'pi.first_names',
            DB::raw("(SELECT student_id_number FROM students s
                      JOIN student_pending_student sps2 ON sps2.student_id = s.id
                      WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule"),
            'dept.name as department',
            'ay.academic_year',
        ];
    }

    private function visibleStatusesFor(?string $role): array
    {
        return match ($role) {
            'secretaire' => [
                'pending', 'secretaire_correction',
                'comptable_review', 'chef_division_review', 'chef_cap_review',
                'sec_dir_adjointe_review', 'directrice_adjointe_review',
                'sec_directeur_review', 'directeur_review',
                'ready', 'delivered', 'rejected',
            ],
            'comptable'           => ['comptable_review'],
            'chef-division'       => ['chef_division_review'],
            'chef-cap'            => ['chef_cap_review'],
            'sec-da'              => ['sec_dir_adjointe_review'],
            'directrice-adjointe' => ['directrice_adjointe_review'],
            'sec-dir'             => ['sec_directeur_review'],
            'directeur'           => ['directeur_review'],
            'admin'               => [],
            default               => ['pending'],
        };
    }
}

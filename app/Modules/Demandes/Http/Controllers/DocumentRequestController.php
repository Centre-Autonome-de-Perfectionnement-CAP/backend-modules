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
 * Responsabilité unique : LECTURE (index + show).
 *
 * Chaque objet demande retourné inclut :
 *   - has_flag    (boolean)        — flag actif ou non
 *   - flagged_by  (string|null)    — nom de l'acteur du dernier flag
 *   - flagged_at  (datetime|null)  — date du dernier flag
 *
 * Ces deux derniers champs sont calculés à la volée depuis
 * document_request_histories (dernière entrée validation_flagged).
 *
 * Transitions  → DocumentRequestTransitionController
 * Historique   → DocumentRequestHistoryController
 * Stats        → DocumentRequestStatsController
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

        $demandes = $query->orderBy('dr.updated_at', 'desc')->get();

        // Enrichir chaque demande avec flagged_by / flagged_at
        $demandes = $this->enrichWithFlagMeta($demandes);

        return response()->json([
            'success' => true,
            'data'    => $demandes,
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

        // Enrichir le dossier unique avec flagged_by / flagged_at
        $demande = $this->enrichWithFlagMeta(collect([$demande]))->first();

        return response()->json(['success' => true, 'data' => $demande]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENRICHISSEMENT FLAG META
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ajoute flagged_by et flagged_at à chaque objet de la collection
     * en faisant une seule requête groupée (pas de N+1).
     *
     * Calcul : dernière entrée action_type = 'validation_flagged'
     * dans document_request_histories pour chaque dossier concerné.
     */
    private function enrichWithFlagMeta(\Illuminate\Support\Collection $demandes): \Illuminate\Support\Collection
    {
        // Récupérer uniquement les IDs dont has_flag = true (inutile pour les autres)
        $flaggedIds = $demandes
            ->filter(fn($d) => (bool) $d->has_flag)
            ->pluck('id')
            ->all();

        if (empty($flaggedIds)) {
            // Aucun flag actif : on pose des valeurs null sur tout le monde
            return $demandes->map(function ($d) {
                $d->flagged_by = null;
                $d->flagged_at = null;
                return $d;
            });
        }

        // Une seule requête pour tous les dossiers flaggés
        // On veut la dernière validation_flagged par document_request_id
        $flags = DB::table('document_request_histories as h1')
            ->whereIn('h1.document_request_id', $flaggedIds)
            ->where('h1.action_type', 'validation_flagged')
            ->whereRaw('h1.id = (
                SELECT MAX(h2.id)
                FROM document_request_histories h2
                WHERE h2.document_request_id = h1.document_request_id
                  AND h2.action_type = ?
            )', ['validation_flagged'])
            ->select('h1.document_request_id', 'h1.actor_name', 'h1.created_at')
            ->get()
            ->keyBy('document_request_id');

        return $demandes->map(function ($d) use ($flags) {
            if ($d->has_flag && isset($flags[$d->id])) {
                $d->flagged_by = $flags[$d->id]->actor_name;
                $d->flagged_at = $flags[$d->id]->created_at;
            } else {
                $d->flagged_by = null;
                $d->flagged_at = null;
            }
            return $d;
        });
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
            'dr.has_flag',                                   // ← nouveau
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

<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Services\StageWhatsAppGroupService;
use App\Modules\Stages\Services\SupervisionService;
use App\Modules\Stages\Services\SupervisorInvitationService;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Stagiaires, superviseurs (et leurs invitations), binômes, groupe WhatsApp.
 * Accès : `stages.manage` ; invitations et groupe WhatsApp : `stages.lead`.
 */
class SupervisionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SupervisionService $supervision,
        private readonly SupervisorInvitationService $invitations,
        private readonly StageWhatsAppGroupService $groups,
    ) {}

    // ── Stagiaires ──────────────────────────────────────────────────────

    public function interns(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['current', 'upcoming', 'finished', 'all', 'mine', 'unsupervised'])],
            'q'     => ['nullable', 'string', 'max:100'],
        ]);
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->supervision->interns($data['scope'] ?? 'current', $data['q'] ?? null, $user),
            'counts'  => $this->supervision->counts($user),
            'me'      => ['id' => $user->id, 'is_lead' => StageAccess::isLead($user), 'is_supervisor' => StageAccess::isSupervisor($user)],
        ]);
    }

    public function setSupervisor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stage_ids'     => ['required', 'array', 'min:1', 'max:200'],
            'stage_ids.*'   => ['integer', 'min:1'],
            'supervisor_id' => ['required', 'integer', 'min:1'],
            'action'        => ['required', Rule::in(['add', 'remove'])],
        ]);

        return $this->guard(function () use ($data, $request) {
            $n = $this->supervision->setSupervisor($data['stage_ids'], (int) $data['supervisor_id'], $data['action'], $request->user());

            return $this->successResponse(['changed' => $n], $n === 0 ? 'Aucun changement.'
                : ($data['action'] === 'add' ? 'Superviseur attribué.' : 'Superviseur retiré.'));
        });
    }

    // ── Superviseurs ────────────────────────────────────────────────────

    public function supervisors(): JsonResponse
    {
        $counts = DB::table('stage_supervisions as s')
            ->join('stage_requests as r', 'r.id', '=', 's.stage_request_id')
            ->where('r.date_fin', '>=', BusinessHours::today()->toDateString())
            ->groupBy('s.user_id')->pluck(DB::raw('COUNT(*)'), 's.user_id');

        return $this->successResponse(StageAccess::supervisors()->map(fn ($u) => [
            'id' => $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name),
            'email' => $u->email, 'phone' => $u->phone, 'since' => $u->created_at,
            'active_interns' => (int) ($counts[$u->id] ?? 0),
        ])->values());
    }

    public function invitationsIndex(): JsonResponse
    {
        return $this->successResponse($this->invitations->list());
    }

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numeros' => ['required', 'string', 'max:2000'],
            'nom'     => ['nullable', 'string', 'max:120'],
        ]);
        $phones = preg_split('/[\n,;]+/', $data['numeros']) ?: [];
        $name   = isset($data['nom']) ? (trim(strip_tags($data['nom'])) ?: null) : null;

        return $this->guard(function () use ($phones, $name, $request) {
            $r = $this->invitations->invite($phones, $name, $request->user());
            $parts = [];
            if ($r['invited']) $parts[] = count($r['invited']) . ' invitation(s) envoyée(s) sur WhatsApp';
            if ($r['already_supervisor']) $parts[] = count($r['already_supervisor']) . ' numéro(s) déjà inscrit(s)';
            if ($r['invalid']) $parts[] = count($r['invalid']) . ' numéro(s) invalide(s) : ' . implode(', ', $r['invalid']);

            return $this->successResponse($r, implode(' · ', $parts) . '.');
        });
    }

    public function resendInvitation(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($id, $request) {
            $this->invitations->resend($id, $request->user());
            return $this->successResponse(null, 'Nouveau lien envoyé sur WhatsApp (valable 72 heures).');
        });
    }

    public function revokeInvitation(int $id): JsonResponse
    {
        return $this->guard(function () use ($id) {
            $this->invitations->revoke($id);
            return $this->successResponse(null, 'Invitation annulée : le lien ne fonctionne plus.');
        });
    }

    // ── Binômes / groupes ───────────────────────────────────────────────

    public function teams(): JsonResponse
    {
        return $this->successResponse($this->supervision->teams());
    }

    public function saveTeam(Request $request, ?int $id = null): JsonResponse
    {
        $data = $request->validate([
            'nom'         => ['required', 'string', 'max:80'],
            'stage_ids'   => ['required', 'array', 'min:2', 'max:20'],
            'stage_ids.*' => ['integer', 'min:1'],
        ]);

        return $this->guard(function () use ($id, $data, $request) {
            $teamId = $this->supervision->saveTeam($id, $data['nom'], $data['stage_ids'], $request->user());
            return $this->successResponse(['id' => $teamId], 'Binôme / groupe enregistré.', $id ? 200 : 201);
        });
    }

    public function deleteTeam(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($id, $request) {
            $this->supervision->deleteTeam($id, $request->user());
            return $this->successResponse(null, 'Binôme / groupe supprimé.');
        });
    }

    // ── Groupe WhatsApp ─────────────────────────────────────────────────

    public function whatsappGroup(): JsonResponse
    {
        return $this->successResponse($this->groups->overview());
    }

    public function createWhatsappGroup(Request $request): JsonResponse
    {
        return $this->guard(function () use ($request) {
            $year = (int) BusinessHours::today()->format('Y');
            $this->groups->create($year, $request->user());
            $sync = $this->groups->sync();
            return $this->successResponse($this->groups->overview(),
                "Groupe {$year} créé. Stagiaires ajoutés : {$sync['added']}" . ($sync['invited'] ? ", invités par lien : {$sync['invited']}" : '') . '.');
        });
    }

    public function linkWhatsappGroup(Request $request): JsonResponse
    {
        $data = $request->validate(['lien' => ['required', 'string', 'max:200']]);

        return $this->guard(function () use ($data, $request) {
            $year  = (int) BusinessHours::today()->format('Y');
            $group = $this->groups->link($year, $data['lien'], $request->user());
            $msg   = "Groupe « {$group->name} » utilisé pour {$year}.";
            if (!$group->is_admin) {
                $msg .= " Nommez maintenant le numéro du CAP administrateur du groupe pour qu'il puisse y ajouter les stagiaires.";
            } else {
                $sync = $this->groups->sync();
                $msg .= " Stagiaires ajoutés : {$sync['added']}.";
            }
            return $this->successResponse($this->groups->overview(), $msg);
        });
    }

    public function syncWhatsappGroup(): JsonResponse
    {
        return $this->guard(function () {
            $s = $this->groups->sync();
            return $this->successResponse($this->groups->overview(),
                "Ajoutés : {$s['added']} · invités par lien : {$s['invited']} · retirés : {$s['removed']}" . ($s['errors'] ? " · échecs : {$s['errors']}" : '') . '.');
        });
    }

    public function dissolveWhatsappGroup(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($id, $request) {
            $this->groups->dissolve($id, $request->user());
            return $this->successResponse($this->groups->overview(), 'Groupe dissous : tous les membres ont été retirés.');
        });
    }

    private function guard(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
    }
}

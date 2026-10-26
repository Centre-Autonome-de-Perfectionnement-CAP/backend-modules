<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Suivi des stagiaires (demandes acceptées) : superviseurs (binômes : BinomeService).
 *
 *  - un stagiaire commence sans superviseur ; on lui en attribue ensuite
 *    (plusieurs possibles) parmi les superviseurs inscrits ;
 *  - un superviseur peut s'attribuer lui-même n'importe quel stagiaire ;
 *  - le responsable (ou l'admin) attribue / retire n'importe qui ;
 *  - chaque action d'un superviseur est signalée au responsable.
 */
class SupervisionService
{
    public function __construct(private readonly StageNotifier $notifier) {}

    // ════════════════════════════════════════════════════════════════════
    // STAGIAIRES
    // ════════════════════════════════════════════════════════════════════

    /**
     * « active » = en cours + à venir ; « unsupervised » = actifs sans superviseur.
     *
     * @param 'active'|'current'|'upcoming'|'finished'|'all'|'mine'|'unsupervised' $scope
     */
    public function interns(string $scope, ?string $q, User $viewer): Collection
    {
        $today = BusinessHours::today()->toDateString();
        $query = StageRequest::query()->where('status', StageStatus::ACCEPTED);

        match ($scope) {
            'active'       => $query->where('date_fin', '>=', $today),
            'current'      => $query->where('date_debut', '<=', $today)->where('date_fin', '>=', $today),
            'upcoming'     => $query->where('date_debut', '>', $today),
            'finished'     => $query->where('date_fin', '<', $today),
            'mine'         => $query->whereIn('id', DB::table('stage_supervisions')->where('user_id', $viewer->id)->select('stage_request_id')),
            'unsupervised' => $query->where('date_fin', '>=', $today)->whereNotIn('id', DB::table('stage_supervisions')->select('stage_request_id')),
            default        => null,
        };

        if ($q !== null && trim($q) !== '') {
            $like = '%' . addcslashes(trim($q), '%_\\') . '%';
            $query->where(fn ($w) => $w->where('reference', 'like', $like)->orWhere('nom', 'like', $like)
                ->orWhere('prenoms', 'like', $like)->orWhere('etablissement_origine', 'like', $like));
        }

        $rows = $query->orderBy('date_debut')->limit(500)->get();

        return $this->decorate($rows, $viewer);
    }

    public function counts(User $viewer): array
    {
        $today = BusinessHours::today()->toDateString();
        $base  = fn () => StageRequest::query()->where('status', StageStatus::ACCEPTED);

        return [
            'active'       => $base()->where('date_fin', '>=', $today)->count(),
            'current'      => $base()->where('date_debut', '<=', $today)->where('date_fin', '>=', $today)->count(),
            'upcoming'     => $base()->where('date_debut', '>', $today)->count(),
            'finished'     => $base()->where('date_fin', '<', $today)->count(),
            'unsupervised' => $base()->where('date_fin', '>=', $today)->whereNotIn('id', DB::table('stage_supervisions')->select('stage_request_id'))->count(),
            'mine'         => $base()->whereIn('id', DB::table('stage_supervisions')->where('user_id', $viewer->id)->select('stage_request_id'))->count(),
            'all'          => $base()->count(),
        ];
    }

    /** Ajoute ou retire un superviseur sur plusieurs stagiaires à la fois. */
    public function setSupervisor(array $stageIds, int $supervisorId, string $action, User $by): int
    {
        $isLead = StageAccess::isLead($by);
        if (!$isLead && $supervisorId !== $by->id) {
            throw new BusinessException('Vous pouvez seulement vous attribuer (ou vous retirer) vous-même des stagiaires.', 'STAGE_SUPERVISE_SELF_ONLY', 403);
        }

        $supervisor = StageAccess::supervisors()->firstWhere('id', $supervisorId);
        if (!$supervisor) {
            throw new BusinessException('Superviseur introuvable.', 'STAGE_SUPERVISOR_NOT_FOUND', 404);
        }

        $interns = StageRequest::whereIn('id', $stageIds)->where('status', StageStatus::ACCEPTED)->get();
        if ($interns->count() !== count(array_unique($stageIds))) {
            throw new BusinessException('Seuls les stagiaires acceptés peuvent être suivis par un superviseur.', 'STAGE_NOT_INTERN', 422);
        }

        $name    = trim($supervisor->first_name . ' ' . $supervisor->last_name);
        $changed = collect();

        DB::transaction(function () use ($interns, $supervisorId, $action, $by, $name, $changed) {
            foreach ($interns as $req) {
                if ($action === 'add') {
                    $done = DB::table('stage_supervisions')->insertOrIgnore([
                        'stage_request_id' => $req->id, 'user_id' => $supervisorId,
                        'assigned_by' => $by->id, 'created_at' => now(),
                    ]);
                } else {
                    $done = DB::table('stage_supervisions')
                        ->where('stage_request_id', $req->id)->where('user_id', $supervisorId)->delete();
                }
                if ($done) {
                    $changed->push($req);
                    $this->log($req, $action === 'add' ? 'supervisor_added' : 'supervisor_removed', $by, $name);
                }
            }
        });

        if ($changed->isEmpty()) {
            return 0;
        }
        $this->notifyLink($changed, $supervisor, $name, $action === 'add', $by);
        if (!StageAccess::isLead($by)) {
            $this->notifier->toManagersGeneric(
                'Action d\'un superviseur',
                [$this->who($by) . ($action === 'add' ? ' s\'est attribué ' : ' s\'est retiré de ') . $changed->count() . ' stagiaire(s).'],
                ['Stagiaires' => $changed->map->fullName()->implode(', ')],
                'stage-superviseur',
            );
        }

        return $changed->count();
    }

    // ════════════════════════════════════════════════════════════════════
    // OUTILS
    // ════════════════════════════════════════════════════════════════════

    /** Superviseurs, binôme et indicateurs pour chaque stagiaire. */
    public function decorate(Collection $rows, ?User $viewer = null): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }
        $sup = DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
            ->whereIn('s.stage_request_id', $rows->pluck('id'))
            ->get(['s.stage_request_id', 'u.id', 'u.first_name', 'u.last_name'])
            ->groupBy('stage_request_id');
        $teams = DB::table('stage_teams')->whereIn('id', $rows->pluck('team_id')->filter())->pluck('name', 'id');
        $today = BusinessHours::today()->toDateString();

        return $rows->map(fn (StageRequest $r) => [
            'id'          => $r->id,
            'reference'   => $r->reference,
            'nom_complet' => trim($r->civilite . ' ' . $r->fullName()),
            'whatsapp'    => $r->whatsapp,
            'email'       => $r->email,
            'formation'   => $r->filiere_niveau,
            'etablissement' => $r->etablissement_origine,
            'service'     => $r->service,
            'date_debut'  => $r->date_debut?->toDateString(),
            'date_fin'    => $r->date_fin?->toDateString(),
            'phase'       => $r->date_debut?->toDateString() > $today ? 'upcoming'
                : ($r->date_fin?->toDateString() < $today ? 'finished' : 'current'),
            'team'        => $r->team_id ? ['id' => $r->team_id, 'name' => $teams[$r->team_id] ?? null] : null,
            'supervisors' => ($sup[$r->id] ?? collect())->map(fn ($u) => [
                'id' => $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name),
            ])->values(),
            'supervised_by_me' => $viewer ? ($sup[$r->id] ?? collect())->contains('id', $viewer->id) : false,
            'whatsapp_group' => $r->wa_removed_at ? 'removed' : ($r->wa_added_at ? 'added' : ($r->wa_invite_sent_at ? 'invited' : null)),
        ])->values();
    }

    /**
     * Liaison faite ou défaite : chaque stagiaire concerné est prévenu (nom et
     * contact de son superviseur), et le superviseur reçoit la liste (sauf
     * s'il a fait l'action lui-même).
     */
    private function notifyLink(Collection $interns, object $supervisor, string $name, bool $added, User $by): void
    {
        foreach ($interns as $req) {
            $this->notifier->toDemandeur(
                $req,
                $added ? 'Votre superviseur de stage' : 'Changement de superviseur',
                $added
                    ? ["{$name} suit désormais votre stage. Adressez-vous à lui pour toute question sur votre travail, vos rapports ou vos présences."]
                    : ["{$name} ne suit plus votre stage. Vos autres superviseurs, ou le responsable de division, restent vos interlocuteurs."],
                $added ? array_filter(['Superviseur' => $name, 'Téléphone' => $supervisor->phone, 'Email' => $supervisor->email]) : ['Superviseur' => $name],
                ctaLabel: 'Votre espace stagiaire',
                ctaUrl: $this->notifier->spaceUrl('/stage'),
                context: 'stage-superviseur',
            );
        }

        if ((int) $supervisor->id === (int) $by->id) {
            return;
        }
        $list = $interns->map(fn (StageRequest $r) => "{$r->reference} - {$r->fullName()}")->implode("\n");
        $this->notifier->toStaff(
            collect([$supervisor]),
            $added ? 'Stagiaires à suivre' : 'Stagiaires retirés de votre suivi',
            [$added
                ? ($interns->count() > 1 ? 'Ces stagiaires vous sont confiés :' : 'Ce stagiaire vous est confié :') . "\n{$list}"
                : ($interns->count() > 1 ? 'Ces stagiaires ne sont plus suivis par vous :' : 'Ce stagiaire n\'est plus suivi par vous :') . "\n{$list}"],
            [],
            'stage-superviseur:' . $interns->first()->reference,
            $interns->count() === 1 ? $this->notifier->staffUrl($interns->first()) : null,
            $interns->count() === 1 ? $interns->first()->reference : null,
        );
    }

    private function who(User $u): string
    {
        return trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) . ' (superviseur)';
    }

    private function log(StageRequest $req, string $action, User $by, ?string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id,
            'action'           => $action,
            'from_status'      => $req->status,
            'to_status'        => $req->status,
            'actor'            => 'staff',
            'user_id'          => $by->id,
            'comment'          => $comment,
        ]);
    }
}

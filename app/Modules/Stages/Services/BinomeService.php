<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\ReportPlan;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Binômes : toujours deux, jamais plus, et toujours au même stade.
 *
 *  - stage : deux stagiaires en cours ou à venir. Le responsable (ou un
 *    superviseur) les réunit, ou l'un le propose à l'autre depuis son espace ;
 *  - demande : deux demandes pas encore étudiées. L'un le propose à l'autre
 *    (formulaire ou suivi de la demande) ; une fois confirmé, le responsable
 *    décide pour les deux à la fois (mêmes service et dates, une note de
 *    service chacun).
 *  - terminé : deux stagiaires dont le stage est fini (enregistrés après
 *    coup, pour déposer leur rapport final). Réuni par le personnel seulement.
 * Un stagiaire déjà en binôme n'est plus proposé. Si l'un ne poursuit pas
 * (demande non retenue, expirée, désistement), le binôme est défait et
 * l'autre continue seul. Chacun est prévenu, ainsi que les responsables.
 *
 * Rapport final : « individuel » (chacun le sien, par défaut) ou « commun »
 * (un même mémoire : déposé par l'un, il vaut pour les deux). Choisi par le
 * personnel, modifiable tant qu'aucun rapport final n'est déposé.
 */
class BinomeService
{
    public const INVITE_DAYS = 7;

    public const STAGE = 'stage';
    public const DEMANDE = 'demande';
    public const TERMINE = 'termine';

    public const FINAL_INDIVIDUEL = 'individuel';
    public const FINAL_COMMUN = 'commun';
    public const FINAL_MODES = [self::FINAL_INDIVIDUEL => 'Individuel (chacun le sien)', self::FINAL_COMMUN => 'Commun (un même mémoire)'];

    public function __construct(private readonly StageNotifier $notifier) {}

    // ════════════════════════════════════════════════════════════════════
    // PROGICIEL
    // ════════════════════════════════════════════════════════════════════

    /**
     * Vue du responsable : binômes formés, demandes en binôme en cours
     * d'étude, propositions entre stagiaires en attente, stagiaires libres.
     */
    public function board(): array
    {
        [$teams, $requests] = $this->teamRows()->partition(fn ($t) => $t['accepted'])
            ->map(fn ($part) => $part->map(fn ($t) => array_diff_key($t, ['accepted' => 0]))->values());
        $invites = DB::table('stage_team_invites')->where('status', 'pending')->where('expires_at', '>', now())->orderByDesc('id')->get();
        $people  = StageRequest::whereIn('id', $invites->pluck('from_stage_id')->concat($invites->pluck('to_stage_id'))->unique())->get()->keyBy('id');

        return [
            'teams'      => $teams,
            'requests'   => $requests,
            'invites'    => $invites->filter(fn ($i) => isset($people[$i->from_stage_id], $people[$i->to_stage_id]))->map(fn ($i) => [
                'id' => $i->id, 'from' => $this->card($people[$i->from_stage_id]), 'to' => $this->card($people[$i->to_stage_id]),
                'phase' => $people[$i->from_stage_id]->status === StageStatus::ACCEPTED ? self::STAGE : self::DEMANDE,
                'created_at' => $this->iso($i->created_at), 'expires_at' => $this->iso($i->expires_at),
            ])->values(),
            'candidates' => $this->candidates(),
        ];
    }

    /** Binômes avec leurs membres ; « accepted » : tous en stage (sinon demande en cours d'étude). */
    private function teamRows(): Collection
    {
        $teams   = DB::table('stage_teams')->orderByDesc('id')->get();
        $members = StageRequest::whereIn('team_id', $teams->pluck('id'))->get()->groupBy('team_id');

        return $teams->map(fn ($t) => [
            'id'         => $t->id,
            'name'       => $t->name,
            'origin'     => $t->from_request ? 'demande' : ($t->created_by ? 'responsable' : 'stagiaires'),
            'final_mode' => $t->final_mode ?? self::FINAL_INDIVIDUEL,
            'final_locked' => $this->finalDeposited(($members[$t->id] ?? collect())->pluck('id')->all()),
            'created_at' => $this->iso($t->created_at),
            'accepted'   => ($members[$t->id] ?? collect())->every(fn ($r) => $r->status === StageStatus::ACCEPTED),
            'members'    => ($members[$t->id] ?? collect())->map(fn (StageRequest $r) => $this->card($r))->values(),
        ])->filter(fn ($t) => count($t['members']) > 0)->values();
    }

    /**
     * Stagiaires sans binôme : en cours ou à venir, et terminés dont le
     * rapport final n'est pas encore validé (ils peuvent encore le déposer à deux).
     */
    public function candidates(): Collection
    {
        $validated = StageReport::where('period_key', ReportPlan::FINAL_KEY)
            ->whereIn('status', ['approved', 'supervisor_approved'])->pluck('stage_request_id');

        return StageRequest::where('status', StageStatus::ACCEPTED)->whereNull('team_id')->whereNotNull('date_fin')
            ->where(fn ($q) => $q->where('date_fin', '>=', BusinessHours::today()->toDateString())
                ->orWhere(fn ($q) => $q->whereNotNull('activated_at')->whereNotIn('id', $validated)))
            ->orderBy('nom')->get()->map(fn (StageRequest $r) => $this->card($r))->values();
    }

    /** Mode du rapport final d'un binôme (individuel si aucun binôme). */
    public function finalMode(?int $teamId): string
    {
        if (!$teamId || !$this->finalReady()) {
            return self::FINAL_INDIVIDUEL;
        }

        return DB::table('stage_teams')->where('id', $teamId)->value('final_mode') === self::FINAL_COMMUN
            ? self::FINAL_COMMUN : self::FINAL_INDIVIDUEL;
    }

    /** Colonnes du rapport final commun présentes (migration passée) ? */
    public function finalReady(): bool
    {
        if (Cache::get('stage-team-final-ready')) {
            return true;
        }
        $ok = Schema::hasColumn('stage_teams', 'final_mode') && Schema::hasColumn('stage_intern_invitations', 'pair_id');
        if ($ok) {
            Cache::put('stage-team-final-ready', true, 3600);
        }

        return $ok;
    }

    /** Change le mode du rapport final, tant qu'aucun rapport final n'est déposé. */
    public function setFinalMode(int $teamId, string $mode, User $by): void
    {
        if (!$this->finalReady()) {
            throw new BusinessException('Ce réglage sera disponible dans quelques minutes.', 'STAGE_TEAM_FINAL_NOT_READY', 503);
        }
        $mode = $this->checkMode($mode);
        $team = DB::table('stage_teams')->where('id', $teamId)->first();
        if (!$team) {
            throw new BusinessException('Binôme introuvable.', 'STAGE_TEAM_NOT_FOUND', 404);
        }
        if (($team->final_mode ?? self::FINAL_INDIVIDUEL) === $mode) {
            return;
        }
        $members = StageRequest::where('team_id', $teamId)->get();
        DB::transaction(function () use ($teamId, $mode, $members) {
            DB::table('stage_teams')->where('id', $teamId)->lockForUpdate()->first();
            if ($this->finalDeposited($members->pluck('id')->all())) {
                throw new BusinessException('Un rapport final est déjà déposé : ce réglage ne peut plus changer.', 'STAGE_TEAM_FINAL_LOCKED', 409);
            }
            DB::table('stage_teams')->where('id', $teamId)->update(['final_mode' => $mode, 'updated_at' => now()]);
        });
        foreach ($members as $me) {
            $this->log($me, 'team_final_mode', $by, 'Rapport final : ' . mb_strtolower(self::FINAL_MODES[$mode]));
            $partner = $members->firstWhere('id', '!=', $me->id);
            $this->notifier->toDemandeur($me, 'Rapport final du binôme', [$this->finalLine($mode, $partner)],
                ...$this->cta($me, false, 'Ouvrir mes rapports', '/rapports'), context: 'stage-binome');
        }
    }

    /** L'autre demande d'un binôme formé dès la demande (décidées ensemble). */
    public function requestPartner(StageRequest $req): ?StageRequest
    {
        if (!$req->team_id || !DB::table('stage_teams')->where('id', $req->team_id)->value('from_request')) {
            return null;
        }

        return StageRequest::where('team_id', $req->team_id)->where('id', '!=', $req->id)->first();
    }

    /**
     * Forme un binôme ; $by null = les deux se sont choisis. $finalMode :
     * rapport final individuel (par défaut) ou commun (choix du personnel).
     */
    public function form(StageRequest $a, StageRequest $b, ?User $by, ?string $name = null, ?string $finalMode = null): int
    {
        $finalMode = $by && $finalMode !== null ? $this->checkMode($finalMode) : self::FINAL_INDIVIDUEL;
        if ($a->id === $b->id) {
            throw new BusinessException('Un binôme compte deux stagiaires différents.', 'STAGE_TEAM_SIZE', 422);
        }

        $phase = null;
        $teamId = DB::transaction(function () use ($a, $b, $by, $name, $finalMode, &$phase) {
            // Verrou : deux formations simultanées ne prennent jamais la même personne.
            $pair = StageRequest::whereIn('id', [$a->id, $b->id])->lockForUpdate()->get()->keyBy('id');
            [$a, $b] = [$pair[$a->id] ?? null, $pair[$b->id] ?? null];
            // Le personnel réunit des stagiaires (en cours, à venir ou terminés) ;
            // les intéressés eux-mêmes, des demandes ou des stagiaires en cours.
            $phase = $this->assertFree($a, null, $by ? [self::STAGE, self::TERMINE] : [self::DEMANDE, self::STAGE]);
            $this->assertFree($b, $phase);

            $name = trim(strip_tags((string) $name));
            $id = DB::table('stage_teams')->insertGetId([
                'name'         => mb_substr($name !== '' ? $name : 'Binôme ' . mb_strtoupper($a->nom) . ' / ' . mb_strtoupper($b->nom), 0, 80),
                'from_request' => $phase === self::DEMANDE,
                'created_by'   => $by?->id,
                'created_at'   => now(), 'updated_at' => now(),
            ] + ($this->finalReady() ? ['final_mode' => $finalMode] : []));
            foreach ([[$a, $b], [$b, $a]] as [$me, $partner]) {
                $me->team_id = $id;
                $me->save();
                $this->log($me, 'team_joined', $by, "Binôme avec {$partner->fullName()} ({$partner->reference})");
            }
            $this->closeInvites([$a->id, $b->id]);

            return $id;
        });

        $demande = $phase === self::DEMANDE;
        foreach ([[$a, $b], [$b, $a]] as [$me, $partner]) {
            $this->notifier->toDemandeur($me, $demande ? 'Demande en binôme confirmée' : 'Vous êtes en binôme', array_values(array_filter([
                $demande
                    ? "Votre demande de stage est désormais liée à celle de {$partner->fullName()} : elles seront étudiées ensemble, pour les mêmes service et dates."
                    : "Vous formez désormais un binôme avec {$partner->fullName()}.",
                $demande ? null : $this->finalLine($finalMode, $partner),
            ])), ['Votre binôme' => $partner->fullName(), 'Sa référence' => $partner->reference],
                ...$this->cta($me, $demande, $phase === self::TERMINE ? 'Ouvrir mes rapports' : null, $phase === self::TERMINE ? '/rapports' : '/stage'),
                context: 'stage-binome');
        }
        $this->notifier->toFollowers([$a, $b], $demande ? 'Demande de stage en binôme' : 'Binôme formé', [
            $demande ? 'Deux demandes de stage seront à étudier ensemble (même décision pour les deux).'
                : ($by ? trim($by->first_name . ' ' . $by->last_name) . ' a formé un binôme.' : 'Deux stagiaires ont formé un binôme depuis leur espace.'),
        ], ['Binôme' => "{$a->fullName()} ({$a->reference}) et {$b->fullName()} ({$b->reference})",
            'Rapport final' => $demande ? null : self::FINAL_MODES[$finalMode]], 'stage-binome',
            $demande ? $this->notifier->staffUrl($a) : $this->binomesUrl());

        return $teamId;
    }

    public function dissolve(int $teamId, User $by): void
    {
        $team = DB::table('stage_teams')->where('id', $teamId)->first();
        if (!$team) {
            throw new BusinessException('Binôme introuvable.', 'STAGE_TEAM_NOT_FOUND', 404);
        }
        $members = $this->unlink($teamId, $by, 'Binôme défait');

        foreach ($members as $r) {
            $this->notifier->toDemandeur($r, 'Binôme défait', ['Votre binôme a été défait par le responsable de votre stage.'],
                ...$this->cta($r, (bool) $team->from_request), context: 'stage-binome');
        }
        if (!StageAccess::isLead($by) && $members->isNotEmpty()) {
            $this->notifier->toFollowers($members->all(), 'Binôme défait', [
                trim($by->first_name . ' ' . $by->last_name) . ' (superviseur) a défait un binôme.',
            ], ['Stagiaires' => $members->map->fullName()->implode(' et ')], 'stage-binome', $this->binomesUrl());
        }
    }

    /**
     * L'un ne poursuit pas (demande non retenue, expirée, désistement) : le
     * binôme est défait, l'autre continue seul et en est informé.
     */
    public function release(StageRequest $req, string $why, bool $notify = true): void
    {
        $this->closeInvites([$req->id]);
        if (!$req->team_id) {
            return;
        }
        $teamId = $req->team_id;
        $demande = (bool) DB::table('stage_teams')->where('id', $teamId)->value('from_request');
        $others = $this->unlink($teamId, null, $why)->where('id', '!=', $req->id);
        foreach ($notify ? $others : [] as $other) {
            $this->notifier->toDemandeur($other, 'Votre binôme ne poursuit pas', [
                "{$req->fullName()} ne poursuit pas : " . mb_strtolower($why) . '.',
                $demande ? 'Votre demande de stage continue, seule.' : 'Vous poursuivez votre stage seul(e).',
            ], ...$this->cta($other, $demande), context: 'stage-binome');
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // DEMANDEUR / STAGIAIRE (suivi de la demande, espace stagiaire)
    // ════════════════════════════════════════════════════════════════════

    public function overview(StageRequest $req): array
    {
        $partner = $req->team_id ? StageRequest::where('team_id', $req->team_id)->where('id', '!=', $req->id)->first() : null;
        $pending = fn () => DB::table('stage_team_invites')->where('status', 'pending')->where('expires_at', '>', now());
        $sent = $pending()->where('from_stage_id', $req->id)->first();
        $received = $pending()->where('to_stage_id', $req->id)->orderBy('id')->get();
        $others = StageRequest::whereIn('id', collect([$sent?->to_stage_id])->concat($received->pluck('from_stage_id'))->filter())->get()->keyBy('id');

        return [
            'team'     => $req->team_id ? [
                'name'    => DB::table('stage_teams')->where('id', $req->team_id)->value('name'),
                'partner' => $partner ? $this->card($partner, false) : null,
                'final_mode' => $this->finalMode($req->team_id),
            ] : null,
            'can_invite' => !$req->team_id && in_array($this->phase($req), [self::DEMANDE, self::STAGE], true),
            'sent'     => $sent && isset($others[$sent->to_stage_id])
                ? ['id' => $sent->id, 'to' => $this->card($others[$sent->to_stage_id], false), 'expires_at' => $this->iso($sent->expires_at)] : null,
            'received' => $received->filter(fn ($i) => isset($others[$i->from_stage_id]))->map(fn ($i) => [
                'id' => $i->id, 'from' => $this->card($others[$i->from_stage_id], false), 'expires_at' => $this->iso($i->expires_at),
            ])->values(),
        ];
    }

    /**
     * La demande ou le stagiaire qu'on peut proposer comme binôme à
     * quelqu'un qui en est au stade $phase (vérifié avant tout enregistrement).
     */
    public function target(string $reference, string $phase): StageRequest
    {
        $to = StageRequest::where('reference', strtoupper(preg_replace('/\s+/', '', $reference)))->first();
        if (!$to || $this->phase($to) !== $phase) {
            throw new BusinessException($phase === self::DEMANDE
                ? 'Aucune demande de stage en attente d\'étude ne correspond à cette référence. Votre binôme doit avoir déposé sa demande, pas encore traitée.'
                : 'Aucun stagiaire en cours ou à venir ne correspond à cette référence. Vérifiez-la.', 'STAGE_TEAM_REF_UNKNOWN', 422);
        }
        if ($to->team_id) {
            throw new BusinessException('Cette personne est déjà en binôme.', 'STAGE_TEAM_TAKEN', 409);
        }

        return $to;
    }

    public function invite(StageRequest $from, string $reference): void
    {
        $phase = $this->assertFree($from, null);
        $to = $this->target($reference, $phase);
        if ($to->id === $from->id) {
            throw new BusinessException('Indiquez la référence de l\'autre personne, pas la vôtre.', 'STAGE_TEAM_SELF', 422);
        }
        $pending = DB::table('stage_team_invites')->where('status', 'pending')->where('expires_at', '>', now());
        if ((clone $pending)->where('from_stage_id', $from->id)->exists()) {
            throw new BusinessException('Vous avez déjà une proposition en attente : annulez-la d\'abord.', 'STAGE_TEAM_PENDING', 409);
        }
        // Il vous l'a déjà proposé : on forme directement le binôme.
        $reverse = (clone $pending)->where('from_stage_id', $to->id)->where('to_stage_id', $from->id)->first();
        if ($reverse) {
            $this->respond($from, $reverse->id, true);
            return;
        }

        DB::table('stage_team_invites')->insert([
            'from_stage_id' => $from->id, 'to_stage_id' => $to->id, 'status' => 'pending',
            'expires_at' => now()->addDays(self::INVITE_DAYS), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $demande = $phase === self::DEMANDE;
        $this->notifier->toDemandeur($to, 'Proposition de binôme', [
            "{$from->fullName()} vous propose de faire votre stage en binôme" . ($demande ? ' : vos deux demandes seraient étudiées ensemble.' : '.'),
            ($demande ? 'Ouvrez le suivi de votre demande' : 'Ouvrez votre espace stagiaire (Mon stage)')
                . ' pour accepter ou refuser, dans les ' . self::INVITE_DAYS . ' jours.',
        ], ['Proposé par' => $from->fullName(), 'Sa référence' => $from->reference],
            ...$this->cta($to, $demande, 'Répondre'), context: 'stage-binome-invitation');
    }

    public function respond(StageRequest $me, int $inviteId, bool $accept): void
    {
        $inv = DB::table('stage_team_invites')->where('id', $inviteId)->where('to_stage_id', $me->id)
            ->where('status', 'pending')->where('expires_at', '>', now())->first();
        if (!$inv) {
            throw new BusinessException('Cette proposition n\'est plus valable.', 'STAGE_TEAM_INVITE_GONE', 410);
        }
        $from = StageRequest::findOrFail($inv->from_stage_id);

        if ($accept) {
            $this->form($from, $me, null);
            DB::table('stage_team_invites')->where('id', $inv->id)->update(['status' => 'accepted', 'responded_at' => now(), 'updated_at' => now()]);
            return;
        }

        DB::table('stage_team_invites')->where('id', $inv->id)->update(['status' => 'declined', 'responded_at' => now(), 'updated_at' => now()]);
        $this->notifier->toDemandeur($from, 'Proposition de binôme refusée', [
            "{$me->fullName()} n'a pas accepté votre proposition de binôme.",
        ], ...$this->cta($from, $this->phase($from) === self::DEMANDE), context: 'stage-binome-invitation');
    }

    public function cancel(StageRequest $from): void
    {
        DB::table('stage_team_invites')->where('from_stage_id', $from->id)->where('status', 'pending')
            ->update(['status' => 'cancelled', 'responded_at' => now(), 'updated_at' => now()]);
    }

    // ── Interne ──────────────────────────────────────────────────────────

    /** Stade où l'on peut former un binôme : demande pas encore étudiée, stage en cours / à venir, ou terminé. */
    private function phase(StageRequest $r): ?string
    {
        if ($r->status === StageStatus::SUBMITTED) {
            return self::DEMANDE;
        }
        if ($r->status !== StageStatus::ACCEPTED || !$r->date_fin) {
            return null;
        }

        return $r->date_fin->toDateString() >= BusinessHours::today()->toDateString() ? self::STAGE : self::TERMINE;
    }

    /**
     * @param string[] $allowed stades permis (si $phase n'est pas donné)
     * @return string le stade (vérifié égal à $phase s'il est donné)
     */
    private function assertFree(?StageRequest $r, ?string $phase, array $allowed = [self::DEMANDE, self::STAGE]): string
    {
        $found = $r ? $this->phase($r) : null;
        if ($found === null || ($phase !== null ? $found !== $phase : !in_array($found, $allowed, true))) {
            throw new BusinessException(match ($phase) {
                self::STAGE   => 'Les deux stagiaires doivent être tous les deux en cours (ou à venir) : un stagiaire terminé ne fait binôme qu\'avec un autre stagiaire terminé.',
                self::TERMINE => 'Les deux stagiaires doivent avoir tous les deux terminé leur stage : un stagiaire terminé ne fait binôme qu\'avec un autre stagiaire terminé.',
                default       => in_array(self::TERMINE, $allowed, true)
                    ? 'Seuls des stagiaires (en cours, à venir ou terminés) peuvent former un binôme.'
                    : 'Un binôme se forme entre deux demandes pas encore étudiées, ou entre deux stagiaires en cours ou à venir.',
            }, 'STAGE_NOT_INTERN', 422);
        }
        if ($r->team_id) {
            throw new BusinessException("{$r->fullName()} est déjà en binôme : défaites d'abord ce binôme.", 'STAGE_TEAM_TAKEN', 409);
        }

        return $found;
    }

    /** @return Collection<int, StageRequest> les membres détachés */
    private function unlink(int $teamId, ?User $by, string $why): Collection
    {
        return DB::transaction(function () use ($teamId, $by, $why) {
            $members = StageRequest::where('team_id', $teamId)->get();
            foreach ($members as $r) {
                $r->team_id = null;
                $r->save();
                $this->log($r, 'team_left', $by, $why, $by ? null : 'systeme');
            }
            DB::table('stage_teams')->where('id', $teamId)->delete();

            return $members;
        });
    }

    private function closeInvites(array $ids): void
    {
        DB::table('stage_team_invites')->where('status', 'pending')
            ->where(fn ($q) => $q->whereIn('from_stage_id', $ids)->orWhereIn('to_stage_id', $ids))
            ->update(['status' => 'cancelled', 'responded_at' => now(), 'updated_at' => now()]);
    }

    /** Bouton du message : suivi de la demande, ou espace stagiaire. */
    private function cta(StageRequest $r, bool $demande, ?string $label = null, string $path = '/stage'): array
    {
        return $demande
            ? ['ctaLabel' => $label ?? 'Suivre ma demande', 'ctaUrl' => $this->notifier->suiviUrl($r)]
            : ['ctaLabel' => $label ?? 'Mon espace stagiaire', 'ctaUrl' => $this->notifier->spaceUrl($path)];
    }

    private function checkMode(string $mode): string
    {
        if (!isset(self::FINAL_MODES[$mode])) {
            throw new BusinessException('Choisissez le type de rapport final.', 'STAGE_TEAM_FINAL_MODE', 422);
        }

        return $mode;
    }

    /** Phrase expliquant le rapport final du binôme au stagiaire. */
    private function finalLine(string $mode, ?StageRequest $partner): string
    {
        return $mode === self::FINAL_COMMUN
            ? 'Rapport final commun : vous déposez un seul rapport final pour vous deux' . ($partner ? ' (avec ' . $partner->fullName() . ')' : '')
                . '. Déposé par l\'un, il vaut pour les deux.'
            : 'Rapport final individuel : chacun dépose le sien.';
    }

    /** Un rapport final est-il déjà déposé par l'un de ces stagiaires ? */
    private function finalDeposited(array $stageIds): bool
    {
        return $stageIds !== [] && StageReport::whereIn('stage_request_id', $stageIds)->where('period_key', ReportPlan::FINAL_KEY)->exists();
    }

    /** Fiche courte ; côté demandeur / stagiaire, sans coordonnées. */
    private function card(StageRequest $r, bool $staff = true): array
    {
        $card = ['reference' => $r->reference, 'nom_complet' => trim($r->civilite . ' ' . $r->fullName())];

        return $staff ? ['id' => $r->id] + $card + [
            'etablissement' => $r->etablissement_origine,
            'date_debut' => $r->date_debut?->toDateString(), 'date_fin' => $r->date_fin?->toDateString(),
            'termine' => $this->phase($r) === self::TERMINE,
        ] : $card;
    }

    private function iso(?string $at): ?string
    {
        return $at ? \Carbon\CarbonImmutable::parse($at)->toIso8601String() : null;
    }

    private function binomesUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/binomes';
    }

    /** Sans membre du personnel : le demandeur (ou le système, pour une dissolution automatique). */
    private function log(StageRequest $req, string $action, ?User $by, string $comment, ?string $actor = null): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id,
            'action'           => $action,
            'from_status'      => $req->status,
            'to_status'        => $req->status,
            'actor'            => $by ? 'staff' : ($actor ?? 'demandeur'),
            'user_id'          => $by?->id,
            'comment'          => $comment,
        ]);
    }
}

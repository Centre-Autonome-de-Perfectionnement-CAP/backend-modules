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
 * Binômes : toujours deux, jamais plus, et toujours au même stade.
 *
 *  - stage : deux stagiaires en cours ou à venir. Le responsable (ou un
 *    superviseur) les réunit, ou l'un le propose à l'autre depuis son espace ;
 *  - demande : deux demandes pas encore étudiées. L'un le propose à l'autre
 *    (formulaire ou suivi de la demande) ; une fois confirmé, le responsable
 *    décide pour les deux à la fois (mêmes service et dates, une note de
 *    service chacun).
 * Un stagiaire déjà en binôme n'est plus proposé. Si l'un ne poursuit pas
 * (demande non retenue, expirée, désistement), le binôme est défait et
 * l'autre continue seul. Chacun est prévenu, ainsi que les responsables.
 */
class BinomeService
{
    public const INVITE_DAYS = 7;

    public const STAGE = 'stage';
    public const DEMANDE = 'demande';

    public function __construct(private readonly StageNotifier $notifier) {}

    // ════════════════════════════════════════════════════════════════════
    // PROGICIEL
    // ════════════════════════════════════════════════════════════════════

    public function teams(): Collection
    {
        $teams   = DB::table('stage_teams')->orderBy('name')->get();
        $members = StageRequest::whereIn('team_id', $teams->pluck('id'))->get()->groupBy('team_id');

        // Binômes de stagiaires (une demande en binôme y entre une fois acceptée).
        return $teams->filter(fn ($t) => ($members[$t->id] ?? collect())->every(fn ($r) => $r->status === StageStatus::ACCEPTED))->map(fn ($t) => [
            'id'      => $t->id,
            'name'    => $t->name,
            'members' => ($members[$t->id] ?? collect())->map(fn (StageRequest $r) => $this->card($r))->values(),
        ])->values();
    }

    /** Stagiaires en cours ou à venir, pas encore en binôme. */
    public function candidates(): Collection
    {
        return StageRequest::where('status', StageStatus::ACCEPTED)->whereNull('team_id')
            ->where('date_fin', '>=', BusinessHours::today()->toDateString())
            ->orderBy('nom')->get()->map(fn (StageRequest $r) => $this->card($r))->values();
    }

    /** L'autre demande d'un binôme formé dès la demande (décidées ensemble). */
    public function requestPartner(StageRequest $req): ?StageRequest
    {
        if (!$req->team_id || !DB::table('stage_teams')->where('id', $req->team_id)->value('from_request')) {
            return null;
        }

        return StageRequest::where('team_id', $req->team_id)->where('id', '!=', $req->id)->first();
    }

    /** Forme un binôme ; $by null = les deux se sont choisis. */
    public function form(StageRequest $a, StageRequest $b, ?User $by, ?string $name = null): int
    {
        if ($a->id === $b->id) {
            throw new BusinessException('Un binôme compte deux stagiaires différents.', 'STAGE_TEAM_SIZE', 422);
        }

        $phase = null;
        $teamId = DB::transaction(function () use ($a, $b, $by, $name, &$phase) {
            // Verrou : deux formations simultanées ne prennent jamais la même personne.
            $pair = StageRequest::whereIn('id', [$a->id, $b->id])->lockForUpdate()->get()->keyBy('id');
            [$a, $b] = [$pair[$a->id] ?? null, $pair[$b->id] ?? null];
            $phase = $this->assertFree($a, $by ? self::STAGE : null);
            $this->assertFree($b, $phase);

            $name = trim(strip_tags((string) $name));
            $id = DB::table('stage_teams')->insertGetId([
                'name'         => mb_substr($name !== '' ? $name : 'Binôme ' . mb_strtoupper($a->nom) . ' / ' . mb_strtoupper($b->nom), 0, 80),
                'from_request' => $phase === self::DEMANDE,
                'created_by'   => $by?->id,
                'created_at'   => now(), 'updated_at' => now(),
            ]);
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
            $this->notifier->toDemandeur($me, $demande ? 'Demande en binôme confirmée' : 'Vous êtes en binôme', [
                $demande
                    ? "Votre demande de stage est désormais liée à celle de {$partner->fullName()} : elles seront étudiées ensemble, pour les mêmes service et dates."
                    : "Vous formez désormais un binôme avec {$partner->fullName()}.",
            ], ['Votre binôme' => $partner->fullName(), 'Sa référence' => $partner->reference],
                ...$this->cta($me, $demande), context: 'stage-binome');
        }
        $this->notifier->toFollowers([$a, $b], $demande ? 'Demande de stage en binôme' : 'Binôme formé', [
            $demande ? 'Deux demandes de stage seront à étudier ensemble (même décision pour les deux).'
                : ($by ? trim($by->first_name . ' ' . $by->last_name) . ' a formé un binôme.' : 'Deux stagiaires ont formé un binôme depuis leur espace.'),
        ], ['Binôme' => "{$a->fullName()} ({$a->reference}) et {$b->fullName()} ({$b->reference})"], 'stage-binome',
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
            ] : null,
            'can_invite' => !$req->team_id && $this->phase($req) !== null,
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

    /** Stade où l'on peut former un binôme : demande pas encore étudiée, ou stage en cours / à venir. */
    private function phase(StageRequest $r): ?string
    {
        if ($r->status === StageStatus::SUBMITTED) {
            return self::DEMANDE;
        }

        return $r->status === StageStatus::ACCEPTED && $r->date_fin?->toDateString() >= BusinessHours::today()->toDateString()
            ? self::STAGE : null;
    }

    /** @return string le stade (vérifié égal à $phase s'il est donné) */
    private function assertFree(?StageRequest $r, ?string $phase): string
    {
        $found = $r ? $this->phase($r) : null;
        if ($found === null || ($phase !== null && $found !== $phase)) {
            throw new BusinessException($phase === self::STAGE
                ? 'Seuls des stagiaires en cours ou à venir peuvent former un binôme.'
                : 'Un binôme se forme entre deux demandes pas encore étudiées, ou entre deux stagiaires en cours ou à venir.', 'STAGE_NOT_INTERN', 422);
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
    private function cta(StageRequest $r, bool $demande, ?string $label = null): array
    {
        return $demande
            ? ['ctaLabel' => $label ?? 'Suivre ma demande', 'ctaUrl' => $this->notifier->suiviUrl($r)]
            : ['ctaLabel' => $label ?? 'Mon espace stagiaire', 'ctaUrl' => $this->notifier->spaceUrl('/stage')];
    }

    /** Fiche courte ; côté demandeur / stagiaire, sans coordonnées. */
    private function card(StageRequest $r, bool $staff = true): array
    {
        $card = ['reference' => $r->reference, 'nom_complet' => trim($r->civilite . ' ' . $r->fullName())];

        return $staff ? ['id' => $r->id] + $card + [
            'etablissement' => $r->etablissement_origine,
            'date_debut' => $r->date_debut?->toDateString(), 'date_fin' => $r->date_fin?->toDateString(),
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

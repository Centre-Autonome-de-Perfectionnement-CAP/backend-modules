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
 * Binômes de stagiaires : toujours deux, jamais plus.
 *
 *  - le responsable (ou un superviseur) forme un binôme avec deux stagiaires
 *    encore libres ;
 *  - un stagiaire le propose depuis son espace avec la référence de l'autre,
 *    qui accepte ou refuse ;
 *  - un stagiaire déjà en binôme n'est plus proposé : il faut d'abord défaire
 *    son binôme.
 * Les deux stagiaires sont prévenus, ainsi que leurs superviseurs.
 */
class BinomeService
{
    public const INVITE_DAYS = 7;

    public function __construct(private readonly StageNotifier $notifier) {}

    // ════════════════════════════════════════════════════════════════════
    // PROGICIEL
    // ════════════════════════════════════════════════════════════════════

    public function teams(): Collection
    {
        $teams   = DB::table('stage_teams')->orderBy('name')->get();
        $members = StageRequest::whereIn('team_id', $teams->pluck('id'))->get()->groupBy('team_id');

        return $teams->map(fn ($t) => [
            'id'      => $t->id,
            'name'    => $t->name,
            'members' => ($members[$t->id] ?? collect())->map(fn (StageRequest $r) => $this->card($r))->values(),
        ]);
    }

    /** Stagiaires en cours ou à venir, pas encore en binôme. */
    public function candidates(): Collection
    {
        return $this->free()->orderBy('nom')->get()->map(fn (StageRequest $r) => $this->card($r))->values();
    }

    /** Forme un binôme ; $by null = les deux stagiaires se sont choisis. */
    public function form(StageRequest $a, StageRequest $b, ?User $by, ?string $name = null): int
    {
        if ($a->id === $b->id) {
            throw new BusinessException('Un binôme compte deux stagiaires différents.', 'STAGE_TEAM_SIZE', 422);
        }

        $teamId = DB::transaction(function () use ($a, $b, $by, $name) {
            // Verrou : deux formations simultanées ne prennent jamais le même stagiaire.
            $pair = StageRequest::whereIn('id', [$a->id, $b->id])->lockForUpdate()->get()->keyBy('id');
            foreach ([$a->id, $b->id] as $id) {
                $this->assertFree($pair[$id] ?? null);
            }
            [$a, $b] = [$pair[$a->id], $pair[$b->id]];

            $name = trim(strip_tags((string) $name));
            $id = DB::table('stage_teams')->insertGetId([
                'name'       => mb_substr($name !== '' ? $name : 'Binôme ' . $this->short($a) . ' / ' . $this->short($b), 0, 80),
                'created_by' => $by?->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ([[$a, $b], [$b, $a]] as [$me, $partner]) {
                $me->team_id = $id;
                $me->save();
                $this->log($me, 'team_joined', $by, "Binôme avec {$partner->fullName()} ({$partner->reference})");
            }
            // Les propositions encore en attente n'ont plus d'objet.
            DB::table('stage_team_invites')->where('status', 'pending')
                ->where(fn ($q) => $q->whereIn('from_stage_id', [$a->id, $b->id])->orWhereIn('to_stage_id', [$a->id, $b->id]))
                ->update(['status' => 'cancelled', 'responded_at' => now(), 'updated_at' => now()]);

            return $id;
        });

        foreach ([[$a, $b], [$b, $a]] as [$me, $partner]) {
            $this->notifier->toDemandeur($me, 'Vous êtes en binôme', [
                "Vous formez désormais un binôme avec {$partner->fullName()}.",
            ], ['Votre binôme' => $partner->fullName(), 'Sa référence' => $partner->reference],
                ctaLabel: 'Mon espace stagiaire', ctaUrl: $this->notifier->spaceUrl('/stage'), context: 'stage-binome');
        }
        $this->notifier->toFollowers([$a, $b], 'Binôme formé', [
            $by ? trim($by->first_name . ' ' . $by->last_name) . ' a formé un binôme.' : 'Deux stagiaires ont formé un binôme depuis leur espace.',
        ], ['Stagiaires' => "{$a->fullName()} et {$b->fullName()}"], 'stage-binome', $this->binomesUrl());

        return $teamId;
    }

    public function dissolve(int $teamId, User $by): void
    {
        $members = StageRequest::where('team_id', $teamId)->get();
        if (!DB::table('stage_teams')->where('id', $teamId)->exists()) {
            throw new BusinessException('Binôme introuvable.', 'STAGE_TEAM_NOT_FOUND', 404);
        }
        DB::transaction(function () use ($teamId, $members, $by) {
            foreach ($members as $r) {
                $r->team_id = null;
                $r->save();
                $this->log($r, 'team_left', $by, 'Binôme défait');
            }
            DB::table('stage_teams')->where('id', $teamId)->delete();
        });

        foreach ($members as $r) {
            $this->notifier->toDemandeur($r, 'Binôme défait', ['Votre binôme a été défait par le responsable de votre stage.'],
                ctaLabel: 'Mon espace stagiaire', ctaUrl: $this->notifier->spaceUrl('/stage'), context: 'stage-binome');
        }
        if (!StageAccess::isLead($by) && $members->isNotEmpty()) {
            $this->notifier->toFollowers($members->all(), 'Binôme défait', [
                trim($by->first_name . ' ' . $by->last_name) . ' (superviseur) a défait un binôme.',
            ], ['Stagiaires' => $members->map->fullName()->implode(' et ')], 'stage-binome', $this->binomesUrl());
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // ESPACE STAGIAIRE
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
            'can_invite' => !$req->team_id && $this->isActive($req),
            'sent'     => $sent && isset($others[$sent->to_stage_id])
                ? ['id' => $sent->id, 'to' => $this->card($others[$sent->to_stage_id], false), 'expires_at' => $this->iso($sent->expires_at)] : null,
            'received' => $received->filter(fn ($i) => isset($others[$i->from_stage_id]))->map(fn ($i) => [
                'id' => $i->id, 'from' => $this->card($others[$i->from_stage_id], false), 'expires_at' => $this->iso($i->expires_at),
            ])->values(),
        ];
    }

    public function invite(StageRequest $from, string $reference): void
    {
        $ref = strtoupper(preg_replace('/\s+/', '', $reference));
        $to = StageRequest::where('reference', $ref)->first();
        if (!$to || $to->status !== StageStatus::ACCEPTED || !$this->isActive($to)) {
            throw new BusinessException('Aucun stagiaire en cours ou à venir ne correspond à cette référence. Vérifiez-la.', 'STAGE_TEAM_REF_UNKNOWN', 422);
        }
        if ($to->id === $from->id) {
            throw new BusinessException('Indiquez la référence de l\'autre stagiaire, pas la vôtre.', 'STAGE_TEAM_SELF', 422);
        }
        $this->assertFree($from);
        if ($to->team_id) {
            throw new BusinessException('Ce stagiaire est déjà en binôme.', 'STAGE_TEAM_TAKEN', 409);
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
        $this->notifier->toDemandeur($to, 'Proposition de binôme', [
            "{$from->fullName()} vous propose de faire votre stage en binôme.",
            'Ouvrez votre espace stagiaire (Mon stage) pour accepter ou refuser, dans les ' . self::INVITE_DAYS . ' jours.',
        ], ['Stagiaire' => $from->fullName(), 'Sa référence' => $from->reference],
            ctaLabel: 'Répondre', ctaUrl: $this->notifier->spaceUrl('/stage'), context: 'stage-binome-invitation');
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
            DB::table('stage_team_invites')->where('id', $inv->id)->update(['status' => 'accepted', 'responded_at' => now(), 'updated_at' => now()]);
            $this->form($from, $me, null);
            return;
        }

        DB::table('stage_team_invites')->where('id', $inv->id)->update(['status' => 'declined', 'responded_at' => now(), 'updated_at' => now()]);
        $this->notifier->toDemandeur($from, 'Proposition de binôme refusée', [
            "{$me->fullName()} n'a pas accepté votre proposition de binôme.",
        ], ctaLabel: 'Mon espace stagiaire', ctaUrl: $this->notifier->spaceUrl('/stage'), context: 'stage-binome-invitation');
    }

    public function cancel(StageRequest $from): void
    {
        DB::table('stage_team_invites')->where('from_stage_id', $from->id)->where('status', 'pending')
            ->update(['status' => 'cancelled', 'responded_at' => now(), 'updated_at' => now()]);
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function free()
    {
        return StageRequest::where('status', StageStatus::ACCEPTED)->whereNull('team_id')
            ->where('date_fin', '>=', BusinessHours::today()->toDateString());
    }

    private function isActive(StageRequest $r): bool
    {
        return $r->date_fin !== null && $r->date_fin->toDateString() >= BusinessHours::today()->toDateString();
    }

    private function assertFree(?StageRequest $r): void
    {
        if (!$r || $r->status !== StageStatus::ACCEPTED || !$this->isActive($r)) {
            throw new BusinessException('Seuls des stagiaires en cours ou à venir peuvent former un binôme.', 'STAGE_NOT_INTERN', 422);
        }
        if ($r->team_id) {
            throw new BusinessException("{$r->fullName()} est déjà en binôme : défaites d'abord ce binôme.", 'STAGE_TEAM_TAKEN', 409);
        }
    }

    /** Fiche courte ; côté stagiaire, sans coordonnées. */
    private function card(StageRequest $r, bool $staff = true): array
    {
        $card = ['reference' => $r->reference, 'nom_complet' => trim($r->civilite . ' ' . $r->fullName())];

        return $staff ? ['id' => $r->id] + $card + [
            'etablissement' => $r->etablissement_origine,
            'date_debut' => $r->date_debut?->toDateString(), 'date_fin' => $r->date_fin?->toDateString(),
        ] : $card;
    }

    private function short(StageRequest $r): string
    {
        return mb_strtoupper($r->nom);
    }

    private function iso(?string $at): ?string
    {
        return $at ? \Carbon\CarbonImmutable::parse($at)->toIso8601String() : null;
    }

    private function binomesUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/binomes';
    }

    private function log(StageRequest $req, string $action, ?User $by, string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id,
            'action'           => $action,
            'from_status'      => $req->status,
            'to_status'        => $req->status,
            'actor'            => $by ? 'staff' : 'demandeur',
            'user_id'          => $by?->id,
            'comment'          => $comment,
        ]);
    }
}

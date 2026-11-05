<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie de stage : texte seulement, éphémère (7 jours).
 *
 * Discussions :
 *  - « general » : stagiaires en cours + équipe (responsables, superviseurs) ;
 *  - « groupe-{id} » : un superviseur et ses stagiaires en cours, visible par
 *    eux seuls.
 *
 * Rappel WhatsApp : un seul par rafale (10 min après le premier message non
 * lu), plus rien tant que la discussion n'a pas été ouverte, pas si la
 * personne est en train de lire, au plus 2 par jour et par discussion,
 * jamais entre 21 h et 7 h.
 */
class StageChatService
{
    public const GENERAL = 'general';
    public const KEEP_DAYS = 7;
    public const MAX_LENGTH = 2000;
    private const PAGE = 50;
    private const BURST_MINUTES = 10;
    private const READING_MINUTES = 2;
    private const MAX_REMINDERS_PER_DAY = 2;
    private const QUIET = [21, 7]; // de 21 h à 7 h : aucun rappel

    public function __construct(
        private readonly Notifier $notifier,
        private readonly StageNotifier $stageNotifier,
    ) {}

    /** Tables en place (migration passée) : sinon la messagerie reste fermée sans erreur. */
    public function ready(): bool
    {
        // Mémorisé seulement une fois prêt : la messagerie s'ouvre dès la migration passée.
        if (Cache::get('stage-chat-ready')) {
            return true;
        }
        $ready = Schema::hasTable('stage_chat_messages') && Schema::hasTable('stage_chat_reads');
        if ($ready) {
            Cache::put('stage-chat-ready', true, 3600);
        }

        return $ready;
    }

    // ════════════════════════════════════════════════════════════════════
    // QUI VOIT QUOI
    // ════════════════════════════════════════════════════════════════════

    /** @return array{type: string, id: int} */
    public static function staff(User $u): array
    {
        return ['type' => 'staff', 'id' => (int) $u->id];
    }

    /** @return array{type: string, id: int} */
    public static function intern(StageRequest $r): array
    {
        return ['type' => 'intern', 'id' => (int) $r->id];
    }

    /** Discussions d'une personne (clés de canal). */
    public function channelsOf(array $who): array
    {
        if ($who['type'] === 'intern') {
            $req = StageRequest::find($who['id']);
            if (!$req || !$this->isCurrent($req)) {
                return [];
            }
            $sups = DB::table('stage_supervisions')->where('stage_request_id', $req->id)->orderBy('user_id')->pluck('user_id');

            return array_merge($sups->map(fn ($id) => "groupe-{$id}")->all(), [self::GENERAL]);
        }

        $user = User::find($who['id']);
        if (!$user || !StageAccess::canManage($user)) {
            return [];
        }

        return StageAccess::isSupervisor($user) ? ["groupe-{$user->id}", self::GENERAL] : [self::GENERAL];
    }

    public function assertAccess(string $channel, array $who): void
    {
        if (!in_array($channel, $this->channelsOf($who), true)) {
            throw new BusinessException('Cette discussion n\'est pas accessible.', 'STAGE_CHAT_FORBIDDEN', 403);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE / ÉCRITURE
    // ════════════════════════════════════════════════════════════════════

    /** Liste des discussions avec dernier message et non lus. */
    public function overview(array $who): array
    {
        $channels = $this->channelsOf($who);
        $reads = DB::table('stage_chat_reads')->where('reader_type', $who['type'])->where('reader_id', $who['id'])
            ->whereIn('channel', $channels ?: ['-'])->pluck('last_read_id', 'channel');

        $items = [];
        foreach ($channels as $ch) {
            $last = DB::table('stage_chat_messages')->where('channel', $ch)->orderByDesc('id')->first();
            $unread = DB::table('stage_chat_messages')->where('channel', $ch)->where('id', '>', (int) ($reads[$ch] ?? 0))
                ->where(fn ($q) => $q->where('sender_type', '!=', $who['type'])->orWhere('sender_id', '!=', $who['id']))->count();
            $names = $last ? $this->names(collect([$last])) : [];
            $items[] = $this->describe($ch, $who) + [
                'last'   => $last ? [
                    'body'  => mb_strimwidth($last->body, 0, 90, '…'),
                    'at'    => CarbonImmutable::parse($last->created_at)->toIso8601String(),
                    'who'   => $this->isMine($last, $who) ? 'Vous' : $this->shortName($names[$this->key($last)]['name'] ?? ''),
                ] : null,
                'unread' => $unread,
            ];
        }

        return ['channels' => $items, 'unread' => array_sum(array_column($items, 'unread')), 'keep_days' => self::KEEP_DAYS];
    }

    /** Nombre total de non lus (pastille). */
    public function unreadTotal(array $who): int
    {
        return $this->overview($who)['unread'];
    }

    /**
     * Messages d'une discussion (les plus récents, ou ceux après / avant un
     * id) ; la discussion est marquée comme lue.
     */
    public function messages(string $channel, array $who, ?int $after = null, ?int $before = null): array
    {
        $this->assertAccess($channel, $who);

        $q = DB::table('stage_chat_messages')->where('channel', $channel);
        if ($after) {
            $rows = $q->where('id', '>', $after)->orderBy('id')->limit(200)->get();
        } else {
            $rows = $q->when($before, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id')->limit(self::PAGE)->get()->reverse()->values();
        }

        $names = $this->names($rows);
        $maxId = (int) DB::table('stage_chat_messages')->where('channel', $channel)->max('id');
        $readBefore = (int) DB::table('stage_chat_reads')
            ->where(['channel' => $channel, 'reader_type' => $who['type'], 'reader_id' => $who['id']])->value('last_read_id');
        $firstUnread = $after ? null : $rows->first(fn ($m) => $m->id > $readBefore && !$this->isMine($m, $who));
        $this->markRead($channel, $who, $maxId);

        $mineLast = $rows->filter(fn ($m) => $this->isMine($m, $who))->last();

        return [
            'channel'  => $this->describe($channel, $who),
            'messages' => $rows->map(fn ($m) => [
                'id'     => (int) $m->id,
                'body'   => $m->body,
                'at'     => CarbonImmutable::parse($m->created_at)->toIso8601String(),
                'mine'   => $this->isMine($m, $who),
                'sender' => $names[$this->key($m)] ?? ['key' => $this->key($m), 'name' => 'Ancien participant', 'role' => null],
            ])->values(),
            // Séparateur « nouveaux messages » à l'ouverture.
            'first_unread_id' => $firstUnread ? (int) $firstUnread->id : null,
            'has_more' => !$after && $rows->isNotEmpty()
                && DB::table('stage_chat_messages')->where('channel', $channel)->where('id', '<', $rows->first()->id)->exists(),
            // « Vu par » : pour mon dernier message affiché.
            'seen'     => $mineLast ? [
                'id'    => (int) $mineLast->id,
                'count' => DB::table('stage_chat_reads')->where('channel', $channel)->where('last_read_id', '>=', $mineLast->id)
                    ->where(fn ($q) => $q->where('reader_type', '!=', $who['type'])->orWhere('reader_id', '!=', $who['id']))->count(),
            ] : null,
            'keep_days' => self::KEEP_DAYS,
        ];
    }

    public function post(string $channel, array $who, string $body): array
    {
        $this->assertAccess($channel, $who);
        $body = $this->clean($body);
        if ($body === '') {
            throw new BusinessException('Écrivez un message.', 'STAGE_CHAT_EMPTY', 422);
        }

        $id = DB::table('stage_chat_messages')->insertGetId([
            'channel' => $channel, 'sender_type' => $who['type'], 'sender_id' => $who['id'], 'body' => $body, 'created_at' => now(),
        ]);
        $this->markRead($channel, $who, $id);

        return ['id' => $id];
    }

    // ════════════════════════════════════════════════════════════════════
    // TÂCHE (toutes les 5 minutes) : rappels groupés + effacement à 7 jours
    // ════════════════════════════════════════════════════════════════════

    /** @return array{deleted: int, reminded: int} */
    public function tick(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $deleted = DB::table('stage_chat_messages')->where('created_at', '<', $now->subDays(self::KEEP_DAYS))->delete();
        // Lecteurs sans discussion active depuis 7 jours : rien à garder.
        DB::table('stage_chat_reads')->where('updated_at', '<', $now->subDays(self::KEEP_DAYS + 1))->delete();

        $local = $now->setTimezone(BusinessHours::TZ);
        if ($local->hour >= self::QUIET[0] || $local->hour < self::QUIET[1]) {
            return ['deleted' => $deleted, 'reminded' => 0];
        }

        $reminded = 0;
        $active = DB::table('stage_chat_messages')->where('created_at', '>=', $now->subDay())->distinct()->pluck('channel');
        foreach ($active as $channel) {
            $reminded += $this->remindChannel($channel, $now, $local->toDateString());
        }

        return ['deleted' => $deleted, 'reminded' => $reminded];
    }

    private function remindChannel(string $channel, CarbonImmutable $now, string $today): int
    {
        $messages = DB::table('stage_chat_messages')->where('channel', $channel)->orderBy('id')
            ->get(['id', 'sender_type', 'sender_id', 'created_at']);
        if ($messages->isEmpty()) {
            return 0;
        }
        $reads = DB::table('stage_chat_reads')->where('channel', $channel)->get()
            ->keyBy(fn ($r) => $r->reader_type . '-' . $r->reader_id);

        $sent = 0;
        foreach ($this->participants($channel) as $p) {
            $who  = ['type' => $p['type'], 'id' => $p['id']];
            $read = $reads[$p['type'] . '-' . $p['id']] ?? null;
            $unread = $messages->filter(fn ($m) => $m->id > (int) ($read->last_read_id ?? 0) && !$this->isMine($m, $who));
            if ($unread->isEmpty() || !$p['phone']) {
                continue;
            }
            $first = $unread->first();
            if (CarbonImmutable::parse($first->created_at)->gt($now->subMinutes(self::BURST_MINUTES))) {
                continue; // rafale en cours : on attend qu'elle se termine
            }
            if ($read && $read->notified_for_id && (int) $read->last_read_id < (int) $read->notified_for_id) {
                continue; // déjà prévenu, pas encore ouvert
            }
            if ($read && $read->seen_at && CarbonImmutable::parse($read->seen_at)->gt($now->subMinutes(self::READING_MINUTES))) {
                continue; // en train de lire
            }
            $count = $read && $read->notified_day === $today ? (int) $read->notified_count : 0;
            if ($count >= self::MAX_REMINDERS_PER_DAY) {
                continue;
            }

            $n     = $unread->count();
            $title = $this->describe($channel, $who)['title'];
            $this->notifier->whatsapp($p['phone'], WhatsAppText::compose('Messagerie de stage', [
                'Vous avez ' . ($n > 1 ? "*{$n} nouveaux messages*" : '*1 nouveau message*') . " dans « {$title} ».",
                "Pour les lire :\n" . $p['url'],
            ]), "stage-chat:{$channel}:{$p['type']}-{$p['id']}", 'Stages');

            DB::table('stage_chat_reads')->updateOrInsert(
                ['channel' => $channel, 'reader_type' => $p['type'], 'reader_id' => $p['id']],
                ['notified_for_id' => $first->id, 'notified_day' => $today, 'notified_count' => $count + 1, 'updated_at' => now()]
                    + ($read ? [] : ['last_read_id' => 0]),
            );
            $sent++;
        }

        return $sent;
    }

    /** @return array<int, array{type: string, id: int, phone: ?string, url: string}> */
    private function participants(string $channel): array
    {
        $staffUrl  = rtrim((string) config('app.url'), '/') . '/services/stages/messages';
        $internUrl = $this->stageNotifier->spaceUrl('/messages');
        $out = [];

        $interns = $this->currentInterns();
        if ($channel === self::GENERAL) {
            $staff = StageAccess::managers()->concat(StageAccess::supervisors())->unique('id');
        } else {
            $supId   = (int) substr($channel, strlen('groupe-'));
            $staff   = User::whereKey($supId)->get(['id', 'phone']);
            $interns = $interns->whereIn('id', DB::table('stage_supervisions')->where('user_id', $supId)->pluck('stage_request_id'));
        }
        foreach ($staff as $u) {
            $out[] = ['type' => 'staff', 'id' => (int) $u->id, 'phone' => $u->phone ?: null, 'url' => $staffUrl];
        }
        foreach ($interns as $r) {
            $out[] = ['type' => 'intern', 'id' => (int) $r->id, 'phone' => $r->whatsapp ?: null, 'url' => $internUrl];
        }

        return $out;
    }

    // ════════════════════════════════════════════════════════════════════
    // OUTILS
    // ════════════════════════════════════════════════════════════════════

    private function isCurrent(StageRequest $req): bool
    {
        return $req->status === StageStatus::ACCEPTED && $req->activated_at
            && (!$req->date_fin || $req->date_fin->toDateString() >= BusinessHours::today()->toDateString());
    }

    private function currentInterns(): Collection
    {
        return StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->whereDate('date_fin', '>=', BusinessHours::today()->toDateString())
            ->get(['id', 'whatsapp', 'civilite', 'nom', 'prenoms']);
    }

    /** Titre et sous-titre d'une discussion, vus par $who. */
    private function describe(string $channel, array $who): array
    {
        if ($channel === self::GENERAL) {
            return ['key' => $channel, 'kind' => 'general', 'title' => 'Discussion générale',
                'subtitle' => 'Stagiaires en cours et équipe du CAP'];
        }

        $supId = (int) substr($channel, strlen('groupe-'));
        $n = DB::table('stage_supervisions as s')->join('stage_requests as r', 'r.id', '=', 's.stage_request_id')
            ->where('s.user_id', $supId)->where('r.status', StageStatus::ACCEPTED)->whereNotNull('r.activated_at')
            ->whereDate('r.date_fin', '>=', BusinessHours::today()->toDateString())->count();
        $stag = $n > 1 ? "{$n} stagiaires" : "{$n} stagiaire";

        if ($who['type'] === 'staff' && $who['id'] === $supId) {
            return ['key' => $channel, 'kind' => 'group', 'title' => 'Mes stagiaires', 'subtitle' => "{$stag} · visible par vous et vos stagiaires seulement"];
        }
        $sup = User::find($supId, ['id', 'first_name', 'last_name']);
        $name = $sup ? trim($sup->first_name . ' ' . $sup->last_name) : 'Superviseur';
        $many = count(array_filter($this->channelsOf($who), fn ($c) => $c !== self::GENERAL)) > 1;

        return ['key' => $channel, 'kind' => 'group', 'title' => $many ? "Groupe de {$name}" : 'Mon groupe de stage',
            'subtitle' => "{$name} et {$stag}"];
    }

    /** Noms et rôles des expéditeurs. @return array<string, array{key: string, name: string, role: ?string}> */
    private function names(Collection $rows): array
    {
        $out = [];
        $staffIds = $rows->where('sender_type', 'staff')->pluck('sender_id')->unique();
        $internIds = $rows->where('sender_type', 'intern')->pluck('sender_id')->unique();
        if ($staffIds->isNotEmpty()) {
            foreach (User::with('roles')->whereIn('id', $staffIds)->get() as $u) {
                $out["staff-{$u->id}"] = ['key' => "staff-{$u->id}", 'name' => trim($u->first_name . ' ' . $u->last_name),
                    'role' => StageAccess::isSupervisor($u) ? 'Superviseur' : 'Équipe CAP'];
            }
        }
        if ($internIds->isNotEmpty()) {
            foreach (StageRequest::whereIn('id', $internIds)->get(['id', 'nom', 'prenoms']) as $r) {
                $out["intern-{$r->id}"] = ['key' => "intern-{$r->id}", 'name' => trim($r->prenoms . ' ' . mb_strtoupper($r->nom)), 'role' => null];
            }
        }

        return $out;
    }

    private function shortName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        return count($parts) > 1 ? $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.' : $name;
    }

    private function key(object $m): string
    {
        return $m->sender_type . '-' . $m->sender_id;
    }

    private function isMine(object $m, array $who): bool
    {
        return $m->sender_type === $who['type'] && (int) $m->sender_id === (int) $who['id'];
    }

    private function markRead(string $channel, array $who, int $maxId): void
    {
        $row = DB::table('stage_chat_reads')->where(['channel' => $channel, 'reader_type' => $who['type'], 'reader_id' => $who['id']])->first();
        DB::table('stage_chat_reads')->updateOrInsert(
            ['channel' => $channel, 'reader_type' => $who['type'], 'reader_id' => $who['id']],
            ['last_read_id' => max($maxId, (int) ($row->last_read_id ?? 0)), 'seen_at' => now(), 'updated_at' => now()],
        );
    }

    /** Texte seul : balises retirées, espaces normalisés, sauts de ligne gardés (3 au plus). */
    private function clean(string $body): string
    {
        $body = strip_tags($body);
        $body = preg_replace("/[^\\S\n]+/u", ' ', str_replace(["\r\n", "\r"], "\n", $body));
        $body = preg_replace("/\n{3,}/", "\n\n", (string) $body);
        $body = trim((string) $body);

        return mb_substr($body, 0, self::MAX_LENGTH);
    }
}

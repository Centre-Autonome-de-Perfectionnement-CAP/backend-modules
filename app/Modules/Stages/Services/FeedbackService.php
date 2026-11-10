<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Avis des stagiaires et notation du stage.
 *
 * Avis (suggestion, plainte, avis positif ou négatif) : anonyme par défaut,
 * aucun lien avec le stagiaire n'est alors enregistré. Visibles des
 * responsables et des superviseurs, sauf ceux sur l'encadrement (responsables
 * seulement). Trois avis par semaine au plus.
 *
 * Notation : une par mois et une de fin de stage, toujours anonyme. Tout
 * reste interne (responsables seulement), sauf le bloc public « Ce qu'en
 * disent nos stagiaires » : moyenne des critères qui ne visent personne
 * (accueil, conditions, organisation, apprentissage), à partir de 10 notes,
 * et avis positifs choisis par les responsables avec l'accord de leur auteur.
 */
class FeedbackService
{
    public const TYPES = ['suggestion' => 'Suggestion', 'plainte' => 'Plainte', 'positif' => 'Avis positif', 'negatif' => 'Avis négatif'];
    public const SUBJECTS = [
        'encadrement'  => 'Encadrement',
        'locaux'       => 'Locaux et matériel',
        'connexion'    => 'Connexion et outils',
        'organisation' => 'Organisation et horaires',
        'plateforme'   => 'Plateforme',
        'autre'        => 'Autre',
    ];
    /** Critères de la notation du stage ; « public » : peut entrer dans la moyenne publique. */
    public const CRITERIA = [
        'encadrement'   => ['Encadrement', false],
        'accueil'       => ['Accueil et ambiance', true],
        'conditions'    => ['Conditions de travail', true],
        'organisation'  => ['Organisation', true],
        'apprentissage' => ["Ce que j'ai appris", true],
    ];
    public const FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const MAX_FILE_KB = 5120;
    private const WEEKLY_MAX = 3;
    private const PUBLIC_MIN = 10;
    private const END_WINDOW_DAYS = 10;   // jours ouvrés avant la fin : notation de fin ouverte
    private const DISK = 'local';

    public function __construct(
        private readonly StageDocumentService $documents,
        private readonly InternActivationService $activation,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    // AVIS
    // ════════════════════════════════════════════════════════════════════

    /** @param array{type: string, subject: string, message: string, signed?: bool, public_ok?: bool} $data */
    public function send(StageRequest $req, array $data, ?UploadedFile $file): void
    {
        $this->documents->assertReady();
        // Limite par semaine, sans rien garder qui relie un avis anonyme au stagiaire.
        $key = 'stage-avis:' . hash_hmac('sha256', $req->id . '|' . BusinessHours::today()->format('o-W'), (string) config('app.key'));
        Cache::add($key, 0, now()->addDays(8));
        if (Cache::get($key) >= self::WEEKLY_MAX) {
            throw new BusinessException('Vous avez déjà envoyé 3 avis cette semaine. Merci, revenez la semaine prochaine.', 'STAGE_AVIS_LIMIT', 429);
        }

        $id = DB::table('stage_feedbacks')->insertGetId([
            'type' => $data['type'], 'subject' => $data['subject'], 'message' => trim($data['message']),
            'stage_request_id' => !empty($data['signed']) ? $req->id : null,
            'public_ok' => $data['type'] === 'positif' && !empty($data['public_ok']),
            'created_at' => now(),
        ]);
        if ($file) {
            $path = $file->storeAs("stages/avis/{$id}", bin2hex(random_bytes(8)) . '.' . strtolower($file->getClientOriginalExtension()), self::DISK);
            DB::table('stage_feedbacks')->where('id', $id)->update([
                'path' => $path, 'file_name' => mb_substr(preg_replace('/[^\pL\pN ._()-]+/u', '-', $file->getClientOriginalName()), 0, 200),
                'mime' => $file->getMimeType(),
            ]);
        }
        Cache::increment($key);
    }

    /** Avis visibles par ce membre du personnel ; ceux qu'il découvre sont marqués lus. */
    public function staffList(User $user, ?string $type, ?string $subject): array
    {
        $this->documents->assertReady();
        $rows = $this->visible($user)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($subject, fn ($q) => $q->where('subject', $subject))
            ->orderByDesc('id')->limit(300)->get();
        $people = StageRequest::whereIn('id', $rows->pluck('stage_request_id')->filter()->unique())->get()->keyBy('id');
        $lead = StageAccess::isLead($user);

        $list = $rows->map(fn ($f) => [
            'id' => $f->id, 'type' => $f->type, 'type_label' => self::TYPES[$f->type] ?? $f->type,
            'subject' => $f->subject, 'subject_label' => self::SUBJECTS[$f->subject] ?? $f->subject,
            'message' => $f->message, 'author' => $f->stage_request_id ? ($people[$f->stage_request_id]?->fullName() ?? 'Stagiaire') : null,
            'file_name' => $f->file_name, 'new' => $f->read_at === null,
            'public_ok' => (bool) $f->public_ok, 'published' => $f->published_at !== null,
            'can_publish' => $lead && $f->public_ok && $f->type === 'positif', 'can_delete' => $lead,
            'created_at' => $f->created_at,
        ]);
        $unread = $rows->whereNull('read_at')->pluck('id');
        if ($unread->isNotEmpty()) {
            DB::table('stage_feedbacks')->whereIn('id', $unread)->update(['read_at' => now()]);
        }

        return ['items' => $list->values(), 'types' => self::TYPES, 'subjects' => self::SUBJECTS];
    }

    public function unreadCount(User $user): int
    {
        return $this->documents->ready() ? $this->visible($user)->whereNull('read_at')->count() : 0;
    }

    public function publish(int $id, bool $public): void
    {
        $f = DB::table('stage_feedbacks')->where('id', $id)->first();
        if (!$f || !$f->public_ok || $f->type !== 'positif') {
            throw new BusinessException('Seul un avis positif dont l\'auteur l\'a accepté peut être affiché publiquement.', 'STAGE_AVIS_PUBLISH', 422);
        }
        DB::table('stage_feedbacks')->where('id', $id)->update(['published_at' => $public ? now() : null]);
    }

    public function delete(int $id): void
    {
        DB::table('stage_feedbacks')->where('id', $id)->delete();
        $this->documents->disk()->deleteDirectory("stages/avis/{$id}");
    }

    /** @return array{0: string, 1: string, 2: ?string} */
    public function file(User $user, int $id): array
    {
        $f = $this->visible($user)->where('id', $id)->first();
        if (!$f || !$f->path || !$this->documents->disk()->exists($f->path)) {
            throw new BusinessException('Ce fichier n\'existe plus.', 'STAGE_AVIS_NOT_FOUND', 404);
        }

        return [$f->path, $f->file_name, $f->mime];
    }

    private function visible(User $user)
    {
        return DB::table('stage_feedbacks')->when(!StageAccess::isLead($user), fn ($q) => $q->where('subject', '!=', 'encadrement'));
    }

    // ════════════════════════════════════════════════════════════════════
    // NOTATION DU STAGE
    // ════════════════════════════════════════════════════════════════════

    /** Périodes que le stagiaire peut noter maintenant, et ses superviseurs à noter. */
    public function ratingState(StageRequest $req): array
    {
        $this->documents->assertReady();
        $open = array_values(array_filter($this->openPeriods($req), fn ($p) => !$this->rated($req, $p)));

        return [
            'open' => array_map(fn ($p) => ['period' => $p, 'label' => $this->periodLabel($p)], $open),
            'criteria' => collect(self::CRITERIA)->map(fn ($c, $k) => ['key' => $k, 'label' => $c[0]])->values(),
            'supervisors' => $this->titulars($req)->map(fn ($u) => ['id' => $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name)])->values(),
        ];
    }

    /** @param array{period: string, scores: array<string,int>, supervisors?: array<int|string,int>, comment?: ?string} $data */
    public function rate(StageRequest $req, array $data): void
    {
        $this->documents->assertReady();
        $period = $data['period'];
        if (!in_array($period, $this->openPeriods($req), true)) {
            throw new BusinessException('Cette notation n\'est pas ouverte.', 'STAGE_RATING_CLOSED', 422);
        }
        if ($this->rated($req, $period)) {
            throw new BusinessException('Vous avez déjà noté cette période. Merci !', 'STAGE_RATING_DONE', 409);
        }
        $titulars = $this->titulars($req)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $sup = collect($data['supervisors'] ?? [])->only($titulars)->map(fn ($v) => (int) $v)->all();

        DB::table('stage_ratings')->insert([
            'period' => $period, 'voter_hash' => $this->voter($req, $period),
            ...collect(self::CRITERIA)->keys()->mapWithKeys(fn ($k) => [$k => (int) $data['scores'][$k]])->all(),
            'supervisors' => $sup ? json_encode($sup) : null,
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'created_at' => now(),
        ]);
    }

    /** Tableau des responsables : moyennes, évolution, superviseurs, commentaires. */
    public function ratingsBoard(): array
    {
        $this->documents->assertReady();
        $since = BusinessHours::today()->subMonths(12)->startOfMonth();
        $rows = DB::table('stage_ratings')->where('created_at', '>=', $since)->orderByDesc('id')->get();
        $keys = array_keys(self::CRITERIA);
        $avg = fn (Collection $c, string $k) => $c->isEmpty() ? null : round($c->avg($k), 2);

        $bySup = [];
        foreach ($rows as $r) {
            foreach ((array) json_decode((string) $r->supervisors, true) as $id => $score) {
                $bySup[$id][] = (int) $score;
            }
        }
        $names = User::whereIn('id', array_keys($bySup))->get()->keyBy('id');

        return [
            'count'    => $rows->count(),
            'criteria' => collect(self::CRITERIA)->map(fn ($c, $k) => ['key' => $k, 'label' => $c[0], 'average' => $avg($rows, $k)])->values(),
            'months'   => $rows->groupBy(fn ($r) => substr((string) $r->created_at, 0, 7))->sortKeys()
                ->map(fn ($g, $m) => ['month' => $m, 'count' => $g->count(),
                    'average' => round(collect($keys)->avg(fn ($k) => $g->avg($k)), 2)])->values(),
            'supervisors' => collect($bySup)->map(fn ($s, $id) => [
                'name' => ($u = $names[$id] ?? null) ? trim($u->first_name . ' ' . $u->last_name) : 'Superviseur', 'count' => count($s),
                'average' => round(array_sum($s) / count($s), 2),
            ])->sortBy('name')->values(),
            'comments' => $rows->whereNotNull('comment')->take(50)->map(fn ($r) => [
                'comment' => $r->comment, 'period' => $this->periodLabel($r->period), 'created_at' => $r->created_at,
            ])->values(),
        ];
    }

    /** Bloc public « Ce qu'en disent nos stagiaires » ; null s'il n'y a rien à montrer. */
    public function publicSummary(): ?array
    {
        if (!$this->documents->ready()) {
            return null;
        }

        return Cache::remember('stage-public-avis', 600, function () {
            $rows = DB::table('stage_ratings')->where('created_at', '>=', now()->subMonths(12))->get();
            $public = collect(self::CRITERIA)->filter(fn ($c) => $c[1]);
            $quotes = DB::table('stage_feedbacks')->whereNotNull('published_at')->where('public_ok', true)->where('type', 'positif')
                ->orderByDesc('published_at')->limit(6)->pluck('message')->map(fn ($m) => mb_strimwidth($m, 0, 280, '…'))->values();
            $enough = $rows->count() >= self::PUBLIC_MIN;
            if (!$enough && $quotes->isEmpty()) {
                return null;
            }
            $criteria = $enough ? $public->map(fn ($c, $k) => ['label' => $c[0], 'average' => round($rows->avg($k), 1)])->values() : collect();

            return [
                'count'    => $enough ? $rows->count() : null,
                'average'  => $enough ? round($criteria->avg('average'), 1) : null,
                'criteria' => $criteria,
                'quotes'   => $quotes,
            ];
        });
    }

    /** Invitation à la notation de fin de stage (envoyée à l'ouverture des évaluations). */
    public function endPeriodOpen(StageRequest $req): bool
    {
        return in_array('fin', $this->openPeriods($req), true) && !$this->rated($req, 'fin');
    }

    // ── Outils ────────────────────────────────────────────────────────────

    /** @return list<string> */
    private function openPeriods(StageRequest $req): array
    {
        $today = BusinessHours::today();
        $periods = [];
        $start = $req->date_debut_reelle ?? $req->date_debut;
        if ($start && CarbonImmutable::parse($start->toDateString()) <= $today && (!$req->date_fin || $req->date_fin->toDateString() >= $today->toDateString())) {
            $periods[] = $today->format('Y-m');
        }
        if ($req->date_fin) {
            $end = CarbonImmutable::parse($req->date_fin->toDateString(), BusinessHours::TZ);
            if ($this->activation->workingDaysBetween($today, $end) <= self::END_WINDOW_DAYS && $today <= $end->addDays(15)) {
                $periods[] = 'fin';
            }
        }

        return $periods;
    }

    private function rated(StageRequest $req, string $period): bool
    {
        return DB::table('stage_ratings')->where('voter_hash', $this->voter($req, $period))->exists();
    }

    /** Empreinte à clé secrète : empêche de noter deux fois, sans désigner le stagiaire. */
    private function voter(StageRequest $req, string $period): string
    {
        return hash_hmac('sha256', 'stage-rating|' . $req->id . '|' . $period, (string) config('app.key'));
    }

    private function titulars(StageRequest $req): Collection
    {
        return User::whereIn('id', DB::table('stage_supervisions')->where('stage_request_id', $req->id)->pluck('user_id'))->get();
    }

    private function periodLabel(string $p): string
    {
        return $p === 'fin' ? 'Fin de stage' : ucfirst(CarbonImmutable::parse($p . '-01')->locale('fr')->isoFormat('MMMM YYYY'));
    }
}

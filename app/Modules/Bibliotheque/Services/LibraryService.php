<?php

namespace App\Modules\Bibliotheque\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Bibliotheque\Models\LibraryPerson;
use App\Modules\Bibliotheque\Models\LibraryWork;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Soutenance\Models\DefenseSubmission;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\StageRequestService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bibliothèque des travaux du CAP.
 *  - le stagiaire propose son rapport de fin de formation au dépôt du
 *    rapport final (thème, résumé, mots-clés, maître de mémoire) ;
 *  - le responsable reprend aussi les mémoires soutenus et ajoute des
 *    travaux à la main ; lui seul publie ;
 *  - publication « publique » (référencée) ou « par lien » (non référencée,
 *    partagée par le responsable) ; le PDF peut rester non téléchargeable ;
 *  - les coordonnées ne sont jamais affichées : « Contacter » relaie le
 *    message par email (WhatsApp à défaut).
 */
class LibraryService
{
    public const DISK = 'local';
    public const DIR  = 'bibliotheque';
    private const MAX_KEYWORDS = 8;

    public function __construct(
        private readonly ReportService $reports,
        private readonly Notifier $notifier,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    // PROPOSITION DU STAGIAIRE
    // ════════════════════════════════════════════════════════════════════

    /**
     * Rapport final présenté comme rapport de fin de formation. Tant que le
     * responsable ne l'a pas repris, le stagiaire peut corriger sa fiche.
     */
    public function proposeFromReport(StageReport $report, array $data): void
    {
        if ($report->kind !== 'final') {
            return;
        }
        $work = LibraryWork::firstWhere(['source_type' => 'stage_report', 'source_id' => $report->id]);
        if ($work && $work->status !== 'proposed') {
            return; // déjà repris par le responsable : la fiche est la sienne
        }

        DB::transaction(function () use ($report, $data, $work) {
            $work ??= $this->newWork('rapport_stage', 'stage_report', $report->id, 'proposed', null);
            $this->fillFromStage($work, $report->stage);
            $work->fill([
                'title'           => $data['theme'],
                'summary'         => $data['resume'] ?? null,
                'keywords'        => $this->keywords($data['mots_cles'] ?? null),
                'authors_consent' => (bool) ($data['consentement_publication'] ?? false),
            ])->save();
            $this->syncPeople($work, $this->stagePeople($report->stage, $data));
        });
    }

    /** Fiche proposée par le stagiaire, pour la reprendre au dépôt suivant. */
    public function proposalFor(StageReport $report): ?array
    {
        $work = LibraryWork::with('people')->firstWhere(['source_type' => 'stage_report', 'source_id' => $report->id]);
        if (!$work) {
            return null;
        }
        $maitre = $work->people->firstWhere('role', 'maitre');

        return [
            'status'  => $work->status,
            'editable' => $work->status === 'proposed',
            'theme'   => $work->title,
            'resume'  => $work->summary,
            'mots_cles' => implode(', ', $work->keywords ?? []),
            'maitre_nom' => $maitre?->name, 'maitre_fonction' => $maitre?->fonction,
            'maitre_email' => $maitre?->email, 'maitre_telephone' => $maitre?->phone,
            'consentement_publication' => $work->authors_consent,
            'url' => $work->status === 'published' ? $this->publicUrl($work) : null,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // RESPONSABLE
    // ════════════════════════════════════════════════════════════════════

    public function adminList(?string $status, ?string $q): Collection
    {
        return LibraryWork::with('people')
            ->when($status, fn ($w) => $w->where('status', $status))
            ->when($q, fn ($w) => $this->search($w, $q))
            ->orderByRaw("FIELD(status, 'proposed', 'draft', 'published', 'unpublished')")
            ->orderByDesc('id')->limit(300)->get()
            ->map(fn (LibraryWork $w) => $this->row($w));
    }

    public function counts(): array
    {
        return LibraryWork::query()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status')->all();
    }

    /** Travaux qu'on peut encore ajouter : rapports finaux validés, mémoires soutenus. */
    public function candidates(): array
    {
        $taken = fn (string $type) => LibraryWork::where('source_type', $type)->pluck('source_id');

        $reports = StageReport::with('stage')->where('kind', 'final')->whereNotIn('id', $taken('stage_report'))
            ->orderByDesc('id')->limit(200)->get()
            ->filter(fn (StageReport $r) => $r->stage && $this->reports->isValidated($r))
            ->map(fn (StageReport $r) => [
                'type' => 'stage_report', 'id' => $r->id, 'title' => $r->stage->theme_souhaite ?: 'Rapport final de stage',
                'authors' => $r->stage->fullName(), 'reference' => $r->stage->reference,
                'year' => $r->stage->date_fin?->year, 'detail' => $r->stage->filiere_niveau,
            ])->values();

        $defenses = DefenseSubmission::with('department')->where('status', 'completed')->whereNotIn('id', $taken('defense'))
            ->orderByDesc('defense_date')->limit(200)->get()
            ->map(fn (DefenseSubmission $d) => [
                'type' => 'defense', 'id' => $d->id, 'title' => $d->thesis_title,
                'authors' => trim(mb_strtoupper($d->last_name) . ' ' . $d->first_names), 'reference' => $d->student_id_number,
                'year' => $d->defense_date?->year, 'detail' => trim(ucfirst($d->defense_type) . ' · ' . ($d->department?->name ?? '')),
            ])->values();

        return ['reports' => $reports, 'defenses' => $defenses];
    }

    /** Reprend un rapport final validé ou un mémoire soutenu (fiche préremplie, brouillon). */
    public function import(string $type, int $id, User $by): LibraryWork
    {
        if (LibraryWork::where(['source_type' => $type, 'source_id' => $id])->exists()) {
            throw new BusinessException('Ce travail est déjà dans la bibliothèque.', 'LIBRARY_EXISTS', 409);
        }

        return DB::transaction(function () use ($type, $id, $by) {
            if ($type === 'stage_report') {
                $report = StageReport::with('stage')->where('kind', 'final')->findOrFail($id);
                $work = $this->newWork('rapport_stage', $type, $id, 'draft', $by);
                $this->fillFromStage($work, $report->stage);
                $work->fill(['title' => $report->stage->theme_souhaite ?: 'Rapport de fin de formation'])->save();
                $this->syncPeople($work, $this->stagePeople($report->stage, []));

                return $work;
            }

            $d = DefenseSubmission::with(['department', 'professor'])->where('status', 'completed')->findOrFail($id);
            $work = $this->newWork('memoire', 'defense', $id, 'draft', $by);
            $work->fill([
                'title' => $d->thesis_title, 'year' => $d->defense_date?->year,
                'etablissement' => 'École Polytechnique d\'Abomey-Calavi (EPAC)', 'universite' => 'Université d\'Abomey-Calavi',
                'filiere' => $d->department?->name,
                'cycle' => in_array($d->defense_type, ['licence', 'master', 'doctorat'], true) ? $d->defense_type : null,
            ]);
            $thesis = $d->files['thesis'] ?? null;
            if ($thesis && Storage::disk('public')->exists($thesis)) {
                $work->file_path = $this->keep($work, Storage::disk('public')->get($thesis));
                $work->file_size = Storage::disk('public')->size($thesis);
            }
            $work->save();
            $p = $d->professor;
            $this->syncPeople($work, array_filter([
                ['role' => 'auteur', 'name' => trim(mb_strtoupper($d->last_name) . ' ' . $d->first_names), 'email' => $d->email, 'phone' => $d->contacts],
                $p ? ['role' => 'maitre', 'name' => trim($p->first_name . ' ' . mb_strtoupper($p->last_name)), 'email' => $p->email, 'phone' => $p->phone] : null,
            ]));

            return $work;
        });
    }

    /** Travail ajouté à la main (personne encadrée hors du système…). */
    public function createManual(array $data, UploadedFile $file, User $by): LibraryWork
    {
        return DB::transaction(function () use ($data, $file, $by) {
            $work = $this->newWork($data['kind'], 'manuel', null, 'draft', $by);
            $this->applyEdit($work, $data);
            $work->file_path = $this->keep($work, (string) file_get_contents($file->getRealPath()));
            $work->file_size = (int) $file->getSize();
            $work->save();
            $this->syncPeople($work, $data['people']);

            return $work;
        });
    }

    public function update(LibraryWork $work, array $data): LibraryWork
    {
        DB::transaction(function () use ($work, $data) {
            $this->applyEdit($work, $data);
            if ($work->status === 'proposed') {
                $work->status = 'draft'; // repris par le responsable : le stagiaire ne la modifie plus
            }
            $work->save();
            $this->syncPeople($work, $data['people']);
        });

        return $work->fresh('people');
    }

    public function replaceFile(LibraryWork $work, UploadedFile $file): LibraryWork
    {
        $old = $work->file_path;
        $work->fill(['file_path' => $this->keep($work, (string) file_get_contents($file->getRealPath())), 'file_size' => (int) $file->getSize()])->save();
        if ($old && $old !== $work->file_path) {
            Storage::disk(self::DISK)->delete($old);
        }

        return $work;
    }

    /** Publie (ou change la visibilité). Le rapport de stage doit être validé. */
    public function publish(LibraryWork $work, string $visibility, bool $downloadable, string $license, User $by): LibraryWork
    {
        $work->load('people');
        if (!$work->people->contains('role', 'auteur')) {
            throw new BusinessException('Indiquez au moins un auteur.', 'LIBRARY_NO_AUTHOR', 422);
        }
        if (mb_strlen(trim((string) $work->summary)) < 30) {
            throw new BusinessException('Ajoutez un résumé (30 caractères au moins) : c\'est lui que les visiteurs et les moteurs de recherche lisent.', 'LIBRARY_NO_SUMMARY', 422);
        }
        if (!$this->document($work)) {
            throw $work->source_type === 'stage_report' && !$this->validatedReport($work)
                ? new BusinessException('Le rapport doit d\'abord être validé.', 'LIBRARY_NOT_VALIDATED', 422)
                : new BusinessException('Ajoutez le PDF du travail avant de le publier.', 'LIBRARY_NO_FILE', 422);
        }

        $work->fill([
            'status' => 'published', 'visibility' => $visibility, 'downloadable' => $downloadable, 'license' => $license,
            'published_at' => $work->published_at ?? now(), 'published_by' => $by->id,
        ])->save();

        return $work;
    }

    public function unpublish(LibraryWork $work): LibraryWork
    {
        $work->fill(['status' => 'unpublished'])->save();

        return $work;
    }

    public function delete(LibraryWork $work): void
    {
        if ($work->status === 'published') {
            throw new BusinessException('Retirez d\'abord ce travail de la publication.', 'LIBRARY_PUBLISHED', 409);
        }
        if ($work->file_path) {
            Storage::disk(self::DISK)->delete($work->file_path);
        }
        $work->delete();
    }

    /** Nouveau lien de partage : l'ancien ne fonctionne plus. */
    public function renewLink(LibraryWork $work): LibraryWork
    {
        $work->fill(['share_token' => Str::random(32)])->save();

        return $work;
    }

    public function detail(LibraryWork $work): array
    {
        $work->loadMissing('people');

        return $this->row($work) + [
            'summary' => $work->summary, 'keywords' => $work->keywords ?? [],
            'etablissement' => $work->etablissement, 'universite' => $work->universite,
            'filiere' => $work->filiere, 'cycle' => $work->cycle, 'annee_etude' => $work->annee_etude,
            'license' => $work->license, 'downloadable' => $work->downloadable,
            'has_file' => $this->document($work) !== null, 'file_origin' => $this->ownFile($work) ? 'travail' : 'rapport',
            'authors_consent' => $work->authors_consent,
            'people' => $work->people->map(fn (LibraryPerson $p) => $p->only(['id', 'role', 'name', 'fonction', 'email', 'phone']))->values(),
            'share_url' => $this->publicUrl($work) . '?cle=' . $work->share_token,
            'messages' => DB::table('library_contact_messages as m')->leftJoin('library_people as p', 'p.id', '=', 'm.person_id')
                ->where('m.work_id', $work->id)->orderByDesc('m.id')->limit(50)
                ->get(['m.id', 'm.sender_name', 'm.sender_email', 'm.message', 'm.created_at', 'p.name as to_name']),
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // PUBLIC
    // ════════════════════════════════════════════════════════════════════

    public function publicQuery(array $f): LengthAwarePaginator
    {
        return LibraryWork::with(['people' => fn ($p) => $p->where('role', 'auteur')])
            ->where('status', 'published')->where('visibility', 'public')
            ->when($f['q'] ?? null, fn ($w, $q) => $this->search($w, $q))
            ->when($f['annee'] ?? null, fn ($w, $y) => $w->where('year', (int) $y))
            ->when($f['niveau'] ?? null, fn ($w, $c) => $w->where('cycle', $c))
            ->when($f['type'] ?? null, fn ($w, $k) => $w->where('kind', $k))
            ->orderByDesc('year')->orderByDesc('published_at')
            ->paginate(12)->withQueryString();
    }

    /** Années et niveaux présents, pour les filtres. */
    public function facets(): array
    {
        $base = fn () => LibraryWork::where('status', 'published')->where('visibility', 'public');

        return [
            'years'  => $base()->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year')->all(),
            'cycles' => $base()->whereNotNull('cycle')->distinct()->pluck('cycle')->all(),
        ];
    }

    /** Carte d'un travail dans la liste publique. */
    public function publicCard(LibraryWork $w): array
    {
        return [
            'slug' => $w->slug, 'kind' => $w->kind, 'kind_label' => LibraryWork::KINDS[$w->kind] ?? $w->kind,
            'title' => $w->title, 'year' => $w->year, 'formation' => $w->formation(),
            'authors' => $w->people->where('role', 'auteur')->pluck('name')->values(),
            'summary' => $w->summary ? Str::limit($w->summary, 240) : null,
        ];
    }

    /**
     * Fiche publique : jamais d'email ni de téléphone, seulement si la
     * personne peut être contactée (le message est relayé).
     */
    public function publicDetail(LibraryWork $w): array
    {
        $w->loadMissing('people');
        $authors = $w->people->where('role', 'auteur')->pluck('name');
        $institution = trim(($w->etablissement ?? '') . ($w->universite ? ', ' . $w->universite : ''), ', ');
        $kind = LibraryWork::KINDS[$w->kind] ?? $w->kind;
        $key = $w->visibility === 'link' ? '?cle=' . $w->share_token : '';

        return $this->publicCard($w) + [
            'summary' => $w->summary, 'keywords' => $w->keywords ?? [], 'etablissement' => $w->etablissement,
            'universite' => $w->universite, 'institution' => $institution, 'visibility' => $w->visibility,
            'people' => $w->people->map(fn (LibraryPerson $p) => [
                'id' => $p->id, 'role' => $p->role, 'role_label' => LibraryPerson::ROLES[$p->role] ?? $p->role,
                'name' => $p->name, 'fonction' => $p->fonction, 'reachable' => $p->reachable(),
            ])->values(),
            'pdf_url' => $w->downloadable && $this->document($w) ? $this->publicUrl($w) . '/pdf' . $key : null,
            'license' => LibraryWork::LICENSES[$w->license] ?? null,
            'url' => $this->publicUrl($w),
            'citation' => $authors->implode(', ') . ($w->year ? " ({$w->year})" : '') . '. ' . $w->title . '. '
                . $kind . ($institution ? ', ' . $institution : '') . '. ' . $this->publicUrl($w),
        ];
    }

    /**
     * Document du travail : le PDF propre au travail (ajouté ou remplacé par
     * le responsable, mémoire repris) ou, pour un rapport de fin de
     * formation, la version officielle du rapport validé - sans copie :
     * une version corrigée après soutenance est reprise d'elle-même.
     *
     * @return array{disk: string, path: string}|null
     */
    public function document(LibraryWork $work): ?array
    {
        if ($this->ownFile($work)) {
            return ['disk' => self::DISK, 'path' => $work->file_path];
        }
        $report = $this->validatedReport($work);
        $path = $report ? $this->reports->filePath($report, (int) $report->official_version) : null;

        return $path ? ['disk' => StageRequestService::DISK, 'path' => $path] : null;
    }

    public function publicUrl(LibraryWork $work): string
    {
        return rtrim((string) config('app.url'), '/') . '/travaux/' . $work->slug;
    }

    /** Message d'un visiteur à une personne du travail (coordonnées jamais montrées). */
    public function contact(LibraryWork $work, LibraryPerson $person, array $data, string $ip): void
    {
        if ($person->work_id !== $work->id || !$person->reachable()) {
            throw new BusinessException('Cette personne ne peut pas être contactée.', 'LIBRARY_NOT_REACHABLE', 422);
        }
        DB::table('library_contact_messages')->insert([
            'work_id' => $work->id, 'person_id' => $person->id, 'sender_name' => $data['nom'], 'sender_email' => $data['email'],
            'sender_phone' => $data['telephone'] ?? null, 'message' => $data['message'], 'ip_hash' => hash('sha256', $ip),
            'created_at' => now(),
        ]);

        $title = 'Message à propos de votre travail';
        $intro = "{$data['nom']} vous écrit depuis la bibliothèque des travaux du CAP-EPAC, à propos de « {$work->title} » :";
        $reply = 'Pour lui répondre : ' . $data['email'] . (!empty($data['telephone']) ? ' · ' . $data['telephone'] : '') . '.';
        if ($person->email) {
            $this->notifier->email($person->email, $title, 'stages::emails.message', [
                'title' => $title, 'greeting' => 'Bonjour ' . $person->name . ',', 'paragraphs' => [$intro, $reply],
                'fields' => [], 'highlight' => $data['message'], 'highlightTitle' => 'Message', 'ctaLabel' => 'Voir le travail',
                'ctaUrl' => $this->publicUrl($work) . ($work->visibility === 'link' ? '?cle=' . $work->share_token : ''),
            ], toName: $person->name);
        } else {
            $this->notifier->whatsapp($person->phone, WhatsAppText::compose($title, [
                'Bonjour ' . $person->name . ',', $intro, '_' . $data['message'] . '_', $reply,
            ]), 'bibliotheque-contact:' . $work->id, 'Bibliotheque');
        }
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function ownFile(LibraryWork $work): bool
    {
        return $work->file_path && Storage::disk(self::DISK)->exists($work->file_path);
    }

    private function validatedReport(LibraryWork $work): ?StageReport
    {
        if ($work->source_type !== 'stage_report') {
            return null;
        }
        $report = StageReport::find($work->source_id);

        return $report && $this->reports->isValidated($report) ? $report : null;
    }

    private function newWork(string $kind, string $sourceType, ?int $sourceId, string $status, ?User $by): LibraryWork
    {
        return new LibraryWork([
            'kind' => $kind, 'source_type' => $sourceType, 'source_id' => $sourceId, 'status' => $status,
            'share_token' => Str::random(32), 'created_by' => $by?->id,
        ]);
    }

    private function fillFromStage(LibraryWork $work, StageRequest $req): void
    {
        $work->fill([
            'year' => $req->date_fin?->year, 'etablissement' => $req->etablissement_origine,
            'filiere' => $req->filiere ?: $req->filiere_niveau, 'cycle' => $req->cycle, 'annee_etude' => $req->annee_etude,
        ]);
    }

    /** Le stagiaire, son binôme et ses superviseurs ; le maître de mémoire qu'il indique. */
    private function stagePeople(StageRequest $req, array $data): array
    {
        $authors = StageRequest::when($req->team_id, fn ($q) => $q->where('team_id', $req->team_id), fn ($q) => $q->whereKey($req->id))
            ->orderByRaw('id = ? DESC', [$req->id])->get();
        $people = $authors->map(fn (StageRequest $a) => ['role' => 'auteur', 'name' => $a->fullName(), 'email' => $a->email, 'phone' => $a->whatsapp])->all();
        if (!empty($data['maitre_nom'])) {
            $people[] = ['role' => 'maitre', 'name' => $data['maitre_nom'], 'fonction' => $data['maitre_fonction'] ?? null,
                'email' => $data['maitre_email'] ?? null, 'phone' => $data['maitre_telephone'] ?? null];
        }
        foreach (DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.stage_request_id', $req->id)->get(['u.first_name', 'u.last_name', 'u.email', 'u.phone']) as $u) {
            $people[] = ['role' => 'superviseur', 'name' => trim($u->first_name . ' ' . mb_strtoupper($u->last_name)), 'fonction' => 'Superviseur de stage, CAP-EPAC', 'email' => $u->email, 'phone' => $u->phone];
        }

        return $people;
    }

    private function applyEdit(LibraryWork $work, array $d): void
    {
        $work->fill([
            'kind' => $d['kind'] ?? $work->kind, 'title' => $d['title'], 'summary' => $d['summary'] ?? null,
            'keywords' => $this->keywords($d['keywords'] ?? null), 'year' => $d['year'] ?? null,
            'etablissement' => $d['etablissement'] ?? null, 'universite' => $d['universite'] ?? null,
            'filiere' => $d['filiere'] ?? null, 'cycle' => $d['cycle'] ?? null, 'annee_etude' => $d['annee_etude'] ?? null,
        ]);
    }

    /** @param array<int, array{role: string, name: string, fonction?: ?string, email?: ?string, phone?: ?string}> $people */
    private function syncPeople(LibraryWork $work, array $people): void
    {
        $work->people()->delete();
        foreach (array_values($people) as $i => $p) {
            $work->people()->create([
                'role' => $p['role'], 'name' => mb_substr(trim($p['name']), 0, 150), 'fonction' => $p['fonction'] ?? null,
                'email' => $p['email'] ?? null, 'phone' => $p['phone'] ?? null, 'position' => $i,
            ]);
        }
    }

    /** « réseau, sécurité ; IoT » → ['réseau', 'sécurité', 'IoT'] */
    private function keywords(array|string|null $raw): array
    {
        $list = is_array($raw) ? $raw : preg_split('/[,;\n]+/u', (string) $raw);

        return collect($list)->map(fn ($k) => mb_substr(trim(strip_tags((string) $k)), 0, 40))->filter()
            ->unique(fn ($k) => mb_strtolower($k))->take(self::MAX_KEYWORDS)->values()->all();
    }

    private function keep(LibraryWork $work, string $content): string
    {
        $path = self::DIR . '/' . Str::lower(Str::random(24)) . '.pdf';
        Storage::disk(self::DISK)->put($path, $content);

        return $path;
    }

    private function search($query, string $q)
    {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], mb_substr(trim($q), 0, 100)) . '%';

        return $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('summary', 'like', $like)
            ->orWhere('keywords', 'like', $like)->orWhere('filiere', 'like', $like)->orWhere('etablissement', 'like', $like)
            ->orWhereHas('people', fn ($p) => $p->where('name', 'like', $like)));
    }

    private function row(LibraryWork $w): array
    {
        return [
            'id' => $w->id, 'slug' => $w->slug, 'kind' => $w->kind, 'kind_label' => LibraryWork::KINDS[$w->kind] ?? $w->kind,
            'source_type' => $w->source_type, 'status' => $w->status, 'status_label' => LibraryWork::STATUSES[$w->status] ?? $w->status,
            'visibility' => $w->visibility, 'title' => $w->title, 'year' => $w->year, 'formation' => $w->formation(),
            'authors' => $w->people->where('role', 'auteur')->pluck('name')->implode(', '),
            'views' => $w->views, 'downloads' => $w->downloads,
            'url' => $w->status === 'published' ? $this->publicUrl($w) : null,
            'published_at' => $w->published_at?->toIso8601String(),
        ];
    }
}

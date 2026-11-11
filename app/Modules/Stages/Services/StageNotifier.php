<?php

namespace App\Modules\Stages\Services;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications du module Stages (email + WhatsApp).
 *
 *  - demandeur / stagiaire : envoi immédiat, à toute heure ;
 *  - personnel (responsable de division…) : jamais la nuit ni le week-end,
 *    la notification est reportée au prochain créneau ouvré (07h30).
 */
class StageNotifier
{
    private const MODULE = 'Stages';

    public function __construct(private readonly Notifier $notifier) {}

    /**
     * @param string[]             $paragraphs
     * @param array<string,mixed>  $fields
     */
    /**
     * Message à un demandeur ou stagiaire : WhatsApp seul (l'email part à la
     * place si le numéro manque ou si le WhatsApp échoue), jamais la nuit.
     * Document joint ou message officiel ($official : révocation, fin de
     * stage…) : WhatsApp et email.
     */
    public function toDemandeur(
        StageRequest $req,
        string $title,
        array $paragraphs,
        array $fields = [],
        ?string $highlight = null,
        ?string $highlightTitle = null,
        ?string $ctaLabel = null,
        ?string $ctaUrl = null,
        string $context = 'stage',
        ?array $file = null,
        bool $official = false,
    ): void {
        $this->toPerson(
            ['civilite' => $req->civilite, 'name' => $req->fullName(), 'phone' => $req->whatsapp, 'email' => $req->email, 'reference' => $req->reference],
            $title, $paragraphs, $fields, $highlight, $highlightTitle, $ctaLabel, $ctaUrl, $context, $file, $official,
        );
    }

    /**
     * Même message à une personne hors dossier de stage (attestation de
     * travail…) : $who = civilité, nom, téléphone, email, référence.
     *
     * @param array{civilite: ?string, name: string, phone: ?string, email: ?string, reference: string} $who
     */
    public function toPerson(
        array $who,
        string $title,
        array $paragraphs,
        array $fields = [],
        ?string $highlight = null,
        ?string $highlightTitle = null,
        ?string $ctaLabel = null,
        ?string $ctaUrl = null,
        string $context = 'stage',
        ?array $file = null,
        bool $official = false,
    ): void {
        $greeting = 'Bonjour ' . trim(($who['civilite'] ?? '') . ' ' . $who['name']) . ',';
        $at = BusinessHours::nextInternSlot();
        $email = [
            'to'      => $who['email'],
            'to_name' => $who['name'],
            'subject' => "{$title} - {$who['reference']}",
            'view'    => 'stages::emails.message',
            'vars'    => [
                'title'          => $title,
                'greeting'       => $greeting,
                'paragraphs'     => $paragraphs,
                'fields'         => $fields,
                'highlight'      => $highlight,
                'highlightTitle' => $highlightTitle,
                'ctaLabel'       => $ctaLabel,
                'ctaUrl'         => $ctaUrl,
            ],
            'attachments' => $file ? [['disk' => $file['disk'], 'path' => $file['path'], 'name' => $file['name']]] : [],
        ];
        $both = $official || $file !== null;

        $this->notifier->whatsapp(
            $who['phone'],
            WhatsAppText::compose($title, [
                $greeting,
                implode("\n\n", $paragraphs),
                WhatsAppText::fields($fields),
                $highlight ? ($highlightTitle ? "*{$highlightTitle} :*\n{$highlight}" : $highlight) : '',
                $ctaUrl ? (($ctaLabel ? "{$ctaLabel} :\n" : '') . $ctaUrl) : '',
            ]),
            "{$context}:{$who['reference']}",
            self::MODULE,
            fallbackEmail: $both ? [] : $email,
            at: $at,
        );

        // Document joint (ex. note de service en PDF) : envoyé juste après le message.
        if ($file) {
            $this->notifier->whatsappFile(
                $who['phone'], $file['disk'], $file['path'], $file['name'], $file['caption'] ?? $title,
                "{$context}-pdf:{$who['reference']}", self::MODULE,
            );
        }

        if ($both) {
            $this->notifier->email(
                $email['to'], $email['subject'], $email['view'], $email['vars'],
                attachments: $email['attachments'], toName: $email['to_name'],
            );
        }
    }

    /**
     * Notifie les responsables de division concernés, en respectant les
     * heures ouvrées.
     *
     * @param string[]            $paragraphs
     * @param array<string,mixed> $fields
     */
    public function toManagers(StageRequest $req, string $title, array $paragraphs, array $fields = [], string $context = 'stage-staff'): void
    {
        $this->toManagersGeneric($title, $paragraphs, $fields, "{$context}:{$req->reference}", $this->staffUrl($req), $req->reference);
    }

    /**
     * Action d'un stagiaire (demande, rapport, arrivée…) : ses superviseurs et
     * les responsables de division sont prévenus, heures ouvrées respectées.
     *
     * @param StageRequest|StageRequest[] $reqs   un stagiaire, ou les deux d'un binôme
     * @param \Illuminate\Support\Collection|null $also autres membres du personnel à prévenir (invités…)
     * @param string[]                    $paragraphs
     * @param array<string,mixed>         $fields
     */
    public function toFollowers(
        StageRequest|array $reqs, string $title, array $paragraphs, array $fields = [],
        string $context = 'stage-staff', ?string $url = null, ?\Illuminate\Support\Collection $also = null,
    ): void {
        $reqs  = is_array($reqs) ? array_values($reqs) : [$reqs];
        $first = $reqs[0];
        $this->toStaff(
            collect($reqs)->flatMap(fn (StageRequest $r) => $this->supervisorsOf($r))->concat(StageAccess::managers())->concat($also ?? []),
            $title, $paragraphs, $fields, "{$context}:{$first->reference}", $url ?? $this->staffUrl($first), $first->reference,
        );
    }

    /** Superviseurs attribués au stagiaire (comptes actifs). */
    public function supervisorsOf(StageRequest $req): \Illuminate\Support\Collection
    {
        return DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.stage_request_id', $req->id)->whereNull('u.deleted_at')
            ->get(['u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name']);
    }

    /**
     * Notification aux responsables sans dossier précis (ex. action d'un
     * superviseur sur plusieurs stagiaires), heures ouvrées respectées.
     *
     * @param string[]            $paragraphs
     * @param array<string,mixed> $fields
     */
    public function toManagersGeneric(
        string $title, array $paragraphs, array $fields = [], string $context = 'stage-staff',
        ?string $url = null, ?string $reference = null,
    ): void {
        $managers = StageAccess::managers();
        if ($managers->isEmpty()) {
            Log::warning('[Stages] Aucun responsable de division (formation continue) à notifier', ['context' => $context]);
            return;
        }

        $this->toStaff($managers, $title, $paragraphs, $fields, $context, $url, $reference);
    }

    /**
     * Notification à des membres du personnel (responsables, superviseurs),
     * jamais la nuit ni le week-end.
     *
     * @param \Illuminate\Support\Collection<int, object{email: ?string, phone: ?string, first_name: ?string, last_name: ?string}> $recipients
     */
    public function toStaff(
        \Illuminate\Support\Collection $recipients, string $title, array $paragraphs, array $fields = [],
        string $context = 'stage-staff', ?string $url = null, ?string $reference = null,
    ): void {
        $managers = $recipients->unique(fn ($m) => $m->email ?? $m->phone);
        $url ??= rtrim((string) config('app.url'), '/') . '/services/stages';
        $slot = BusinessHours::nextStaffSlot();
        $subject = $reference ? "{$title} - {$reference}" : $title;

        foreach ($managers as $m) {
            $greeting = 'Bonjour ' . trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')) . ',';

            // WhatsApp seul ; l'email part à la place si le numéro manque ou si le WhatsApp échoue.
            $email = !empty($m->email) && filter_var($m->email, FILTER_VALIDATE_EMAIL) ? [
                'to'      => $m->email,
                'to_name' => trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')),
                'subject' => $subject,
                'view'    => 'stages::emails.message',
                'vars'    => [
                    'title' => $title, 'greeting' => $greeting, 'paragraphs' => $paragraphs,
                    'fields' => $fields, 'highlight' => null, 'highlightTitle' => null,
                    'ctaLabel' => 'Ouvrir', 'ctaUrl' => $url,
                ],
            ] : null;

            if (!empty($m->phone)) {
                $this->send('whatsapp', [
                    'phone'   => $m->phone,
                    'message' => WhatsAppText::compose($title, [
                        $greeting,
                        implode("\n\n", $paragraphs),
                        WhatsAppText::fields($fields),
                        "Ouvrir :\n{$url}",
                    ]),
                    'context' => $context,
                    'module'  => self::MODULE,
                ] + ($email ? ['fallback' => ['channel' => 'email', 'payload' => $email]] : []), $slot);
            } elseif ($email) {
                $this->send('email', $email, $slot);
            }
        }
    }

    // ── Récapitulatifs et annonces ───────────────────────────────────────

    /** Tables des notifications regroupées présentes (migration passée) ? */
    public function digestReady(): bool
    {
        if (Cache::get('stage-digest-ready')) {
            return true;
        }
        $ok = Schema::hasTable('stage_digest_items') && Schema::hasTable('stage_report_broadcasts');
        if ($ok) {
            Cache::put('stage-digest-ready', true, 3600);
        }

        return $ok;
    }

    /**
     * Action d'un superviseur (« a validé le rapport de … ») : mise de côté
     * pour le récapitulatif de 17h45 aux responsables, qui peuvent revenir
     * sur chaque décision depuis la plateforme.
     */
    public function supervisorAction(User $by, string $what, ?StageRequest $req = null): void
    {
        $line = trim(($by->first_name ?? '') . ' ' . ($by->last_name ?? '')) . ' ' . $what;
        if ($this->digestReady()) {
            try {
                DB::table('stage_digest_items')->insert(['kind' => 'superviseur', 'line' => mb_substr($line, 0, 500), 'created_at' => now()]);

                return;
            } catch (\Throwable $e) {
                Log::warning('[Stages] Action de superviseur non mise de côté : envoi direct', ['error' => $e->getMessage()]);
            }
        }
        $this->toManagersGeneric('Action d\'un superviseur', [$line . '. Vous pouvez confirmer ou revenir sur cette décision.'], [],
            'stage-superviseur' . ($req ? ":{$req->reference}" : ''), $req ? $this->staffUrl($req) : null, $req?->reference);
    }

    /** Récapitulatif des actions des superviseurs (17h45, jours ouvrés). */
    public function sendSupervisorDigest(): int
    {
        if (!$this->digestReady()) {
            return 0;
        }
        $items = DB::table('stage_digest_items')->where('kind', 'superviseur')->orderBy('id')->get(['id', 'line']);
        if ($items->isEmpty()) {
            return 0;
        }
        $n = $items->count();
        $this->toManagersGeneric(
            "Actions des superviseurs aujourd'hui ({$n})",
            [$items->map(fn ($i) => '- ' . $i->line)->implode("\n"), 'Vous pouvez revenir sur chacune de ces décisions depuis la plateforme.'],
            [], 'stage-superviseur-recap', rtrim((string) config('app.url'), '/') . '/services/stages',
        );
        DB::table('stage_digest_items')->whereIn('id', $items->pluck('id'))->delete();

        return $n;
    }

    /**
     * Annonce à tous les stagiaires en cours et à tout le personnel du
     * module (WhatsApp cadencé ; email de secours ; jamais la nuit pour les
     * stagiaires, heures ouvrées pour le personnel).
     *
     * @param int[] $exceptInterns stagiaires déjà prévenus autrement
     */
    public function toEveryone(string $title, array $paragraphs, string $internUrl, string $staffUrl, string $context, array $exceptInterns = []): int
    {
        $today = BusinessHours::today()->toDateString();
        $interns = StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->where('date_fin', '>=', $today)->whereNotIn('id', $exceptInterns)->get();
        $at = BusinessHours::nextInternSlot();
        foreach ($interns as $r) {
            $greeting = 'Bonjour ' . trim($r->civilite . ' ' . $r->fullName()) . ',';
            $this->notifier->whatsapp(
                $r->whatsapp,
                WhatsAppText::compose($title, [$greeting, implode("\n\n", $paragraphs), "Ouvrir :\n{$internUrl}"]),
                "{$context}:{$r->reference}", self::MODULE, true,
                fallbackEmail: [
                    'to' => $r->email, 'to_name' => $r->fullName(), 'subject' => $title, 'view' => 'stages::emails.message',
                    'vars' => ['title' => $title, 'greeting' => $greeting, 'paragraphs' => $paragraphs, 'fields' => [],
                        'highlight' => null, 'highlightTitle' => null, 'ctaLabel' => 'Ouvrir', 'ctaUrl' => $internUrl],
                ],
                at: $at,
            );
        }
        $this->toStaff(StageAccess::managers()->concat(StageAccess::supervisors()), $title, $paragraphs, [], $context, $staffUrl);

        return $interns->count();
    }

    public function suiviUrl(StageRequest $req): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/')
            . '/stage/suivi?ref=' . urlencode($req->reference);
    }

    /** Adresse d'une page de l'espace stagiaire (« /rapports », « /stage »…). */
    public function spaceUrl(string $path = ''): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/') . '/espace-stagiaire' . $path;
    }

    public function staffUrl(StageRequest $req): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages?ref=' . urlencode($req->reference);
    }

    /** Envoi immédiat, ou différé au prochain créneau ouvré. */
    private function send(string $channel, array $payload, ?\DateTimeInterface $slot): void
    {
        try {
            if ($slot === null) {
                SendNotificationJob::send($channel, $payload);
                return;
            }
            SendNotificationJob::dispatch($channel, $payload)->delay($slot);
        } catch (\Throwable $e) {
            Log::error('[Stages] Notification non programmée', ['channel' => $channel, 'error' => $e->getMessage()]);
        }
    }
}

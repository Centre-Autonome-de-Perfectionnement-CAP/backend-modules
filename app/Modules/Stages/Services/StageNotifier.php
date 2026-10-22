<?php

namespace App\Modules\Stages\Services;

use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use Illuminate\Support\Facades\Log;

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
    ): void {
        $greeting = 'Bonjour ' . trim($req->civilite . ' ' . $req->fullName()) . ',';

        $this->notifier->whatsapp(
            $req->whatsapp,
            WhatsAppText::compose($title, [
                $greeting,
                implode("\n\n", $paragraphs),
                WhatsAppText::fields($fields),
                $highlight ? ($highlightTitle ? "*{$highlightTitle} :*\n{$highlight}" : $highlight) : '',
                $ctaUrl ? (($ctaLabel ? "{$ctaLabel} :\n" : '') . $ctaUrl) : '',
            ]),
            "{$context}:{$req->reference}",
            self::MODULE,
        );

        $this->notifier->email(
            $req->email,
            "{$title} - {$req->reference}",
            'stages::emails.message',
            [
                'title'          => $title,
                'greeting'       => $greeting,
                'paragraphs'     => $paragraphs,
                'fields'         => $fields,
                'highlight'      => $highlight,
                'highlightTitle' => $highlightTitle,
                'ctaLabel'       => $ctaLabel,
                'ctaUrl'         => $ctaUrl,
            ],
            toName: $req->fullName(),
        );
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
                ], $slot);
            }

            if (!empty($m->email) && filter_var($m->email, FILTER_VALIDATE_EMAIL)) {
                $this->send('email', [
                    'to'      => $m->email,
                    'to_name' => trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')),
                    'subject' => $subject,
                    'view'    => 'stages::emails.message',
                    'vars'    => [
                        'title' => $title, 'greeting' => $greeting, 'paragraphs' => $paragraphs,
                        'fields' => $fields, 'highlight' => null, 'highlightTitle' => null,
                        'ctaLabel' => 'Ouvrir', 'ctaUrl' => $url,
                    ],
                ], $slot);
            }
        }
    }

    public function suiviUrl(StageRequest $req): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/')
            . '/stage/suivi?ref=' . urlencode($req->reference);
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

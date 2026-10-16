<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\WhatsApp\Services\WhatsAppBridgeClient;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Notifier — point d'entrée unique des notifications email + WhatsApp.
 *
 * Règles (identiques au module Demandes) :
 *   - tout part en file (queue) : la requête HTTP ne fait que publier des
 *     jobs, elle n'attend jamais le SMTP ni le bridge ;
 *   - un job par canal et par destinataire : les deux canaux ne se
 *     marchent jamais dessus, un échec n'en bloque aucun autre ;
 *   - un dispatch qui échoue (table jobs indisponible…) est journalisé et
 *     n'interrompt jamais le traitement métier appelant ;
 *   - transactionnel (bulk = false) : file `default`, sans délai — perçu
 *     instantané par le destinataire ;
 *   - diffusion de masse (bulk = true) : file `emails` (traitée après
 *     `default`, ne retarde jamais le transactionnel) et envois cadencés
 *     (voir reserveSlot()) pour ne jamais saturer le SMTP ni faire
 *     suspendre le numéro WhatsApp.
 */
class Notifier
{
    public const QUEUE_BULK = 'emails';

    private const BULK_GAP_WA_MS    = 4000;
    private const BULK_GAP_EMAIL_MS = 1000;

    public function __construct(private WhatsAppBridgeClient $bridge) {}

    /** Email depuis une vue Blade. */
    public function email(
        ?string $to,
        string $subject,
        string $view,
        array $vars = [],
        array $attachments = [],
        ?string $toName = null,
        array $cc = [],
        bool $bulk = false,
    ): void {
        if (!$this->isEmail($to)) {
            return;
        }

        $this->dispatch('email', [
            'to' => trim($to), 'to_name' => $toName, 'cc' => $cc, 'subject' => $subject,
            'view' => $view, 'vars' => $vars, 'attachments' => $attachments,
        ], $bulk);
    }

    /** Email depuis une classe Mailable. */
    public function mailable(?string $to, Mailable $mailable, array $cc = [], bool $bulk = false): void
    {
        if (!$this->isEmail($to)) {
            return;
        }

        $this->dispatch('mailable', ['to' => trim($to), 'cc' => $cc, 'mailable' => $mailable], $bulk);
    }

    /**
     * Email (Mailable) puis, SEULEMENT si le serveur SMTP l'a accepté, des
     * WhatsApp d'alerte (ex. « un message vous attend dans la boîte mail »).
     * Si l'email échoue définitivement, aucun WhatsApp n'est envoyé.
     *
     * @param array<int, array{phone: ?string, message: string, context: string, module: string}> $whatsAppAfterSent
     */
    public function mailableThenWhatsApp(?string $to, Mailable $mailable, array $whatsAppAfterSent, array $cc = []): void
    {
        if (!$this->isEmail($to)) {
            Log::warning('[Notifier] Email non envoyé (adresse invalide) : alertes WhatsApp annulées', ['to' => $to]);
            return;
        }

        $followUps = [];
        foreach ($whatsAppAfterSent as $wa) {
            if ($this->hasPhone($wa['phone'] ?? null)) {
                $followUps[] = ['channel' => 'whatsapp', 'payload' => [
                    'phone' => $wa['phone'], 'message' => $wa['message'],
                    'context' => $wa['context'], 'module' => $wa['module'],
                ]];
            }
        }

        $this->dispatch('mailable', [
            'to' => trim($to), 'cc' => $cc, 'mailable' => $mailable, 'after_sent' => $followUps,
        ], false);
    }

    /** Message WhatsApp texte. */
    public function whatsapp(?string $phone, string $message, string $context, string $module, bool $bulk = false): void
    {
        if (!$this->hasPhone($phone)) {
            return;
        }

        $this->dispatch('whatsapp', [
            'phone' => $phone, 'message' => $message, 'context' => $context, 'module' => $module,
        ], $bulk);
    }

    /** Fichier WhatsApp (le fichier doit rester sur le disque jusqu'à l'envoi). */
    public function whatsappFile(
        ?string $phone,
        string $disk,
        string $path,
        string $fileName,
        string $caption,
        string $context,
        string $module,
        bool $bulk = false,
    ): void {
        if (!$this->hasPhone($phone)) {
            return;
        }

        $this->dispatch('whatsapp_file', [
            'phone' => $phone, 'disk' => $disk, 'path' => $path, 'file_name' => $fileName,
            'caption' => $caption, 'context' => $context, 'module' => $module,
        ], $bulk);
    }

    /**
     * Extrait le premier numéro exploitable d'un champ "contacts" quelle que
     * soit sa forme : liste de numéros, objet {phone|telephone|whatsapp},
     * liste d'objets, ou JSON (éventuellement doublement encodé).
     */
    public function phoneFromContacts(mixed $contacts): ?string
    {
        foreach ($this->phoneCandidates($contacts, 0) as $candidate) {
            if ($this->bridge->normalizePhone($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    // ── Interne ──────────────────────────────────────────────────────────────

    private function dispatch(string $channel, array $payload, bool $bulk): void
    {
        try {
            $pending = SendNotificationJob::dispatch($channel, $payload);

            if ($bulk) {
                $pending->onQueue(self::QUEUE_BULK);
                $delay = $this->reserveSlot(str_starts_with($channel, 'whatsapp') ? 'whatsapp' : 'email');
                if ($delay > 0) {
                    $pending->delay(now()->addMilliseconds($delay));
                }
            }
        } catch (\Throwable $e) {
            Log::error('[Notifier] Mise en file impossible', [
                'channel' => $channel,
                'to'      => $payload['to'] ?? ($payload['phone'] ?? null),
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cadence des diffusions de masse : réserve le prochain créneau d'envoi
     * du canal (horloge partagée entre toutes les requêtes et tous les
     * workers) et renvoie le délai en millisecondes.
     *
     *   - WhatsApp : un message toutes les ~4 s (+ 0 à 2 s aléatoires), soit
     *     au plus ~900 messages/heure — rythme « humain » qui évite la
     *     suspension du numéro ;
     *   - email    : un message par seconde (évite le refus du serveur SMTP).
     *
     * Le transactionnel (bulk = false) n'est jamais retardé.
     */
    private function reserveSlot(string $channel): int
    {
        $gapMs    = $channel === 'whatsapp' ? self::BULK_GAP_WA_MS : self::BULK_GAP_EMAIL_MS;
        $jitterMs = $channel === 'whatsapp' ? random_int(0, 2000) : 0;
        $key      = "notifier:bulk-slot:{$channel}";

        try {
            return Cache::lock("{$key}:lock", 5)->block(3, function () use ($key, $gapMs, $jitterMs) {
                $nowMs  = (int) floor(microtime(true) * 1000);
                $lastMs = (int) Cache::get($key, 0);
                $slotMs = max($nowMs, $lastMs + $gapMs) + $jitterMs;
                Cache::put($key, $slotMs, now()->addDay());

                return max(0, $slotMs - $nowMs);
            });
        } catch (\Throwable $e) {
            // Verrou indisponible : on étale quand même de façon aléatoire.
            return random_int(0, 60_000);
        }
    }

    private function isEmail(?string $to): bool
    {
        return is_string($to) && filter_var(trim($to), FILTER_VALIDATE_EMAIL) !== false;
    }

    private function hasPhone(?string $phone): bool
    {
        return is_string($phone) && trim($phone) !== '';
    }

    /** @return string[] */
    private function phoneCandidates(mixed $value, int $depth): array
    {
        if ($depth > 3 || $value === null || $value === '') {
            return [];
        }

        if (is_int($value) || is_float($value)) {
            return [(string) $value];
        }

        if (is_string($value)) {
            $trim = trim($value);
            if ($trim !== '' && ($trim[0] === '[' || $trim[0] === '{' || $trim[0] === '"')) {
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->phoneCandidates($decoded, $depth + 1);
                }
            }
            return [$trim];
        }

        if (is_array($value)) {
            $out = [];
            // Clés explicites d'abord (WhatsApp en priorité).
            foreach (['whatsapp', 'phone', 'telephone', 'tel', 'numero', 'number', 'contact', 'value'] as $key) {
                if (isset($value[$key])) {
                    array_push($out, ...$this->phoneCandidates($value[$key], $depth + 1));
                }
            }
            foreach ($value as $key => $item) {
                if (is_int($key)) {
                    array_push($out, ...$this->phoneCandidates($item, $depth + 1));
                }
            }
            return $out;
        }

        return [];
    }
}

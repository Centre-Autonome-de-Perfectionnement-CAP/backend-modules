<?php

namespace App\Modules\Core\Jobs;

use App\Modules\WhatsApp\Services\WhatsAppBridgeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * SendNotificationJob — job unique d'envoi asynchrone (tous modules).
 *
 * UN job = UN canal = UN destinataire : un échec email n'a aucun effet sur
 * le WhatsApp correspondant (et inversement), et un destinataire en échec
 * ne bloque jamais les autres.
 *
 * Canaux :
 *   'email'          → { to, to_name?, cc?: [], subject, view, vars, attachments?: [{disk, path, name}] }
 *   'mailable'       → { to, cc?: [], mailable: Mailable }
 *   'whatsapp'       → { phone, message, context, module? }
 *   'whatsapp_file'  → { phone, disk, path, file_name, caption?, context, module? }
 *
 * Politique de retry :
 *   - retryUntil() = 24 h pour les incidents transitoires (bridge
 *     déconnecté / saturé, SMTP indisponible) avec backoff progressif ;
 *   - maxExceptions = 6 : une exception répétée ne boucle pas 24 h ;
 *   - les échecs définitifs (numéro invalide / absent de WhatsApp, fichier
 *     introuvable, adresse email invalide) sont journalisés, jamais retentés.
 *
 * Historique : ce job vivait dans App\Modules\Demandes\Jobs. La classe
 * Demandes l'étend désormais (compatibilité des jobs déjà en file).
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Nombre maximal d'exceptions avant échec définitif (failed_jobs). */
    public int $maxExceptions = 6;

    /**
     * Timeout d'une exécution (s) : > timeout fichier du bridge (60 s) + ping
     * /status (5 s), et < retry_after de la connexion queue (90 s).
     */
    public int $timeout = 80;

    /** Backoff progressif entre deux tentatives (secondes). */
    private const BACKOFF = [15, 30, 60, 120, 300, 600];

    public function __construct(
        public readonly string $channel,
        public readonly array  $payload,
    ) {
        // Dispatch dans une transaction → publié seulement après le COMMIT.
        $this->afterCommit();
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(24);
    }

    /** @return int[] */
    public function backoff(): array
    {
        return self::BACKOFF;
    }

    public function handle(WhatsAppBridgeClient $bridge): void
    {
        match ($this->channel) {
            'email'         => $this->handleEmail(),
            'mailable'      => $this->handleMailable(),
            'whatsapp'      => $this->handleWhatsApp($bridge),
            'whatsapp_file' => $this->handleWhatsAppFile($bridge),
            default         => Log::warning("[SendNotificationJob] Canal inconnu : {$this->channel}"),
        };
    }

    // ── Email (vue Blade) ──────────────────────────────────────────────────────

    private function handleEmail(): void
    {
        $p = $this->payload;

        if (!$this->validEmail($p['to'] ?? null)) {
            Log::warning('[SendNotificationJob] Email ignoré : adresse invalide', [
                'to' => $p['to'] ?? null, 'sub' => $p['subject'] ?? null,
            ]);
            return;
        }

        // Pas de catch : SMTP indisponible → retry avec backoff.
        Mail::send($p['view'], $p['vars'] ?? [], function ($m) use ($p) {
            $m->to($p['to'], $p['to_name'] ?? null)->subject($p['subject']);

            foreach ($this->validCc($p['cc'] ?? []) as $cc) {
                $m->cc($cc);
            }

            foreach ($p['attachments'] ?? [] as $att) {
                $disk = $att['disk'] ?? 'public';
                if (!empty($att['path']) && Storage::disk($disk)->exists($att['path'])) {
                    $m->attachFromStorageDisk($disk, $att['path'], $att['name'] ?? basename($att['path']));
                } else {
                    Log::warning('[SendNotificationJob] Pièce jointe email introuvable — ignorée', [
                        'to' => $p['to'], 'path' => $att['path'] ?? null,
                    ]);
                }
            }
        });

        Log::info('[SendNotificationJob] Email envoyé', ['to' => $p['to'], 'sub' => $p['subject']]);
    }

    // ── Email (classe Mailable) ────────────────────────────────────────────────

    private function handleMailable(): void
    {
        $p = $this->payload;
        $mailable = $p['mailable'] ?? null;

        if (!$mailable instanceof Mailable || !$this->validEmail($p['to'] ?? null)) {
            Log::warning('[SendNotificationJob] Mailable ignoré : destinataire ou contenu invalide', [
                'to' => $p['to'] ?? null, 'class' => is_object($mailable) ? get_class($mailable) : null,
            ]);
            return;
        }

        $pending = Mail::to($p['to']);
        $cc = $this->validCc($p['cc'] ?? []);
        if ($cc) {
            $pending->cc($cc);
        }

        // send() (et non queue()) : on est déjà dans le worker.
        $pending->send($mailable);

        Log::info('[SendNotificationJob] Mailable envoyé', ['to' => $p['to'], 'class' => get_class($mailable)]);
    }

    // ── WhatsApp ───────────────────────────────────────────────────────────────

    private function handleWhatsApp(WhatsAppBridgeClient $bridge): void
    {
        $p = $this->payload;

        if (!$bridge->isConnected()) {
            $this->retryLater('Bridge WhatsApp déconnecté');
            return;
        }

        $res = $bridge->sendDetailed($p['phone'], $p['message'], $p['context'] ?? '', $p['module'] ?? 'Demandes');
        $this->handleResult($res, 'WhatsApp');
    }

    private function handleWhatsAppFile(WhatsAppBridgeClient $bridge): void
    {
        $p = $this->payload;

        if (!$bridge->isConnected()) {
            $this->retryLater('Bridge WhatsApp déconnecté (fichier)');
            return;
        }

        $res = $bridge->sendFileDetailed(
            $p['phone'],
            $p['disk'] ?? 'public',
            $p['path'],
            $p['file_name'] ?? basename($p['path']),
            $p['caption'] ?? '',
            $p['context'] ?? '',
            $p['module'] ?? 'Demandes',
        );
        $this->handleResult($res, 'WhatsApp (fichier)');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function validEmail(mixed $email): bool
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** @return string[] */
    private function validCc(array $cc): array
    {
        return array_values(array_filter($cc, fn ($e) => $this->validEmail($e)));
    }

    /** @param array{ok: bool, retryable: bool, http: int, error: ?string} $res */
    private function handleResult(array $res, string $label): void
    {
        $p = $this->payload;

        if ($res['ok']) {
            Log::info("[SendNotificationJob] {$label} envoyé", ['to' => $p['phone'] ?? null, 'ctx' => $p['context'] ?? '']);
            return;
        }

        if ($res['retryable']) {
            $this->retryLater("{$label} : échec transitoire ({$res['error']})");
            return;
        }

        Log::warning("[SendNotificationJob] {$label} non envoyé — échec définitif", [
            'to' => $p['phone'] ?? null, 'ctx' => $p['context'] ?? '',
            'http' => $res['http'], 'error' => $res['error'],
        ]);
    }

    private function retryLater(string $reason): void
    {
        $attempt = max(1, $this->attempts());
        $delay   = self::BACKOFF[min($attempt - 1, count(self::BACKOFF) - 1)];

        Log::warning("[SendNotificationJob] {$reason} — nouvelle tentative dans {$delay}s", [
            'to' => $this->payload['phone'] ?? ($this->payload['to'] ?? null),
            'ctx' => $this->payload['context'] ?? '',
            'attempt' => $attempt,
        ]);

        $this->release($delay);
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('[SendNotificationJob] Notification abandonnée', [
            'channel' => $this->channel,
            'to'      => $this->payload['phone'] ?? ($this->payload['to'] ?? null),
            'ctx'     => $this->payload['context'] ?? ($this->payload['subject'] ?? ''),
            'error'   => $e?->getMessage(),
        ]);
    }
}

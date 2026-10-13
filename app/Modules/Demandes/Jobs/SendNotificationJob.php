<?php

namespace App\Modules\Demandes\Jobs;

use App\Modules\Demandes\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * SendNotificationJob
 *
 * Job Laravel (queue database) pour l'envoi différé des notifications.
 * UN job = UN canal = UN destinataire : un échec email n'a aucun effet sur
 * le WhatsApp correspondant (et inversement), et un destinataire en échec
 * ne bloque jamais les autres.
 *
 * Canaux :
 *   'email'          → payload: { to, to_name?, subject, view, vars, attachments?: [{disk, path, name}] }
 *   'whatsapp'       → payload: { phone, message, context }
 *   'whatsapp_file'  → payload: { phone, disk, path, file_name, caption?, context }
 *
 * POLITIQUE DE RETRY (CORRECTIF 13/10/2026) :
 *   L'ancienne version avait $tries = 3 et faisait release(15) quand le bridge
 *   était déconnecté, en pensant « ne pas brûler de tentative ». Or release()
 *   CONSOMME une tentative : après ~45 s de bridge indisponible, le message
 *   partait en failed_jobs et était perdu. De plus, un échec transitoire du
 *   bridge (429 rate-limit, 5xx, timeout) n'était jamais retenté.
 *
 *   Désormais :
 *     - retryUntil() = fenêtre de 24 h pendant laquelle les incidents
 *       transitoires (bridge déconnecté / injoignable / saturé, SMTP down)
 *       sont retentés avec un backoff progressif ;
 *     - maxExceptions = 6 : une exception répétée (ex. adresse email rejetée
 *       par le SMTP) ne boucle pas pendant 24 h ;
 *     - les échecs définitifs (numéro invalide, numéro absent de WhatsApp,
 *       fichier introuvable) sont journalisés et ne sont PAS retentés.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Nombre maximal d'exceptions avant échec définitif (failed_jobs). */
    public int $maxExceptions = 6;

    /**
     * Timeout max d'une exécution (secondes). Doit rester :
     *   > timeout fichier du bridge (60 s) + ping /status (5 s)
     *   < retry_after de la connexion queue (90 s, config/queue.php),
     * sinon le job serait relancé en parallèle pendant qu'il tourne encore.
     */
    public int $timeout = 80;

    /** Backoff progressif entre deux tentatives (secondes). */
    private const BACKOFF = [15, 30, 60, 120, 300, 600];

    public function __construct(
        public readonly string $channel,
        public readonly array  $payload,
    ) {
        // Si le dispatch a lieu dans une transaction DB, le job n'est publié
        // qu'après le COMMIT : jamais de notification pour un état annulé.
        $this->afterCommit();
    }

    /** Fenêtre de retry pour les incidents transitoires. */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(24);
    }

    /** @return int[] */
    public function backoff(): array
    {
        return self::BACKOFF;
    }

    public function handle(WhatsAppService $whatsApp): void
    {
        match ($this->channel) {
            'email'         => $this->handleEmail(),
            'whatsapp'      => $this->handleWhatsApp($whatsApp),
            'whatsapp_file' => $this->handleWhatsAppFile($whatsApp),
            default         => Log::warning("[SendNotificationJob] Canal inconnu : {$this->channel}"),
        };
    }

    // ── Email ──────────────────────────────────────────────────────────────────

    private function handleEmail(): void
    {
        $p = $this->payload;

        if (empty($p['to']) || !filter_var($p['to'], FILTER_VALIDATE_EMAIL)) {
            Log::warning('[SendNotificationJob] Email ignoré : adresse invalide', [
                'to'  => $p['to'] ?? null,
                'sub' => $p['subject'] ?? null,
            ]);
            return;
        }

        // On ne catche pas l'exception ici : si le SMTP est down, Laravel
        // retente avec le backoff (dans la limite de maxExceptions).
        Mail::send(
            $p['view'],
            $p['vars'] ?? [],
            function ($m) use ($p) {
                $m->to($p['to'], $p['to_name'] ?? null)->subject($p['subject']);

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
            }
        );

        Log::info('[SendNotificationJob] Email envoyé', [
            'to'  => $p['to'],
            'sub' => $p['subject'],
        ]);
    }

    // ── WhatsApp (texte) ───────────────────────────────────────────────────────

    private function handleWhatsApp(WhatsAppService $whatsApp): void
    {
        $p = $this->payload;

        if (!$whatsApp->isConnected()) {
            $this->retryLater('Bridge WhatsApp déconnecté');
            return;
        }

        $res = $whatsApp->sendDetailed($p['phone'], $p['message'], $p['context'] ?? '');
        $this->handleResult($res, 'WhatsApp');
    }

    // ── WhatsApp (fichier) ─────────────────────────────────────────────────────

    private function handleWhatsAppFile(WhatsAppService $whatsApp): void
    {
        $p = $this->payload;

        if (!$whatsApp->isConnected()) {
            $this->retryLater('Bridge WhatsApp déconnecté (fichier)');
            return;
        }

        $res = $whatsApp->sendFileDetailed(
            $p['phone'],
            $p['disk'] ?? 'public',
            $p['path'],
            $p['file_name'] ?? basename($p['path']),
            $p['caption'] ?? '',
            $p['context'] ?? '',
        );
        $this->handleResult($res, 'WhatsApp (fichier)');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * @param array{ok: bool, retryable: bool, http: int, error: ?string} $res
     */
    private function handleResult(array $res, string $label): void
    {
        $p = $this->payload;

        if ($res['ok']) {
            Log::info("[SendNotificationJob] {$label} envoyé", [
                'to'  => $p['phone'] ?? null,
                'ctx' => $p['context'] ?? '',
            ]);
            return;
        }

        if ($res['retryable']) {
            $this->retryLater("{$label} : échec transitoire ({$res['error']})");
            return;
        }

        // Échec définitif : inutile de retenter (numéro invalide / absent de WhatsApp).
        Log::warning("[SendNotificationJob] {$label} non envoyé — échec définitif", [
            'to'    => $p['phone'] ?? null,
            'ctx'   => $p['context'] ?? '',
            'http'  => $res['http'],
            'error' => $res['error'],
        ]);
    }

    private function retryLater(string $reason): void
    {
        $attempt = max(1, $this->attempts());
        $delay   = self::BACKOFF[min($attempt - 1, count(self::BACKOFF) - 1)];

        Log::warning("[SendNotificationJob] {$reason} — nouvelle tentative dans {$delay}s", [
            'to'      => $this->payload['phone'] ?? ($this->payload['to'] ?? null),
            'ctx'     => $this->payload['context'] ?? '',
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

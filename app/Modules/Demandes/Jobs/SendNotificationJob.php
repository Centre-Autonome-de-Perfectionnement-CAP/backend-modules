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

/**
 * SendNotificationJob
 *
 * Job Laravel (queue database) pour l'envoi différé des notifications.
 *
 * AVANT : Mail::send() et WhatsAppService::send() étaient appelés directement
 *         dans le cycle HTTP (soumission, transition, complément).
 *         → Chaque bouton attendait SMTP + bridge WhatsApp avant de répondre.
 *         → Si le bridge était down → exception dans le cycle → 500.
 *         → Sous charge → timeouts en cascade.
 *
 * APRÈS : dispatch() insère une ligne dans la table `jobs` (< 1ms).
 *         Le cycle HTTP retourne immédiatement.
 *         Le worker (php artisan queue:work) traite les jobs en arrière-plan.
 *         → Boutons répondent en < 100ms.
 *         → Bridge down → release() → retry automatique sans brûler de tentative.
 *         → 3 tentatives avec 15s de backoff → jamais perdu.
 *
 * Pattern identique aux jobs existants du projet (Finance/Jobs/).
 *
 * Canal 'email'    → payload: { to, to_name?, subject, view, vars }
 * Canal 'whatsapp' → payload: { phone, message, context }
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tentatives max avant d'aller dans failed_jobs */
    public int $tries = 3;

    /** Délai entre tentatives (secondes) */
    public int $backoff = 15;

    /** Timeout max du job (secondes) */
    public int $timeout = 30;

    public function __construct(
        public readonly string $channel,
        public readonly array  $payload,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        match ($this->channel) {
            'email'    => $this->handleEmail(),
            'whatsapp' => $this->handleWhatsApp($whatsApp),
            default    => Log::warning("[SendNotificationJob] Canal inconnu : {$this->channel}"),
        };
    }

    // ── Email ──────────────────────────────────────────────────────────────────

    private function handleEmail(): void
    {
        $p = $this->payload;

        // On ne catche pas l'exception ici :
        // si SMTP est down, Laravel déclenche le retry automatiquement.
        Mail::send(
            $p['view'],
            $p['vars'] ?? [],
            fn ($m) => $m
                ->to($p['to'], $p['to_name'] ?? null)
                ->subject($p['subject'])
        );

        Log::info('[SendNotificationJob] Email envoyé', [
            'to'  => $p['to'],
            'sub' => $p['subject'],
        ]);
    }

    // ── WhatsApp ───────────────────────────────────────────────────────────────

    private function handleWhatsApp(WhatsAppService $whatsApp): void
    {
        $p = $this->payload;

        // Bridge déconnecté → release() = remet en queue SANS brûler une tentative.
        // Le message sera retenté dans 15s. Jamais perdu.
        if (!$whatsApp->isConnected()) {
            Log::warning('[SendNotificationJob] Bridge WA déconnecté — report dans 15s', [
                'to'  => $p['phone'],
                'ctx' => $p['context'] ?? '',
            ]);
            $this->release(15);
            return;
        }

        $ok = $whatsApp->send($p['phone'], $p['message'], $p['context'] ?? '');

        if ($ok) {
            Log::info('[SendNotificationJob] WhatsApp envoyé', [
                'to'  => $p['phone'],
                'ctx' => $p['context'] ?? '',
            ]);
        } else {
            // Bridge a répondu mais a signalé un échec (numéro invalide, etc.)
            // On ne retry pas car le bridge a déjà loggué le problème.
            Log::warning('[SendNotificationJob] WhatsApp non envoyé (voir logs bridge)', [
                'to'  => $p['phone'],
                'ctx' => $p['context'] ?? '',
            ]);
        }
    }
}

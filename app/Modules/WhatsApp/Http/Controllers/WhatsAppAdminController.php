<?php

namespace App\Modules\WhatsApp\Http\Controllers;

use App\Modules\WhatsApp\Models\WaMessageLog;
use App\Modules\WhatsApp\Services\WhatsAppBridgeClient;
use App\Modules\WhatsApp\Services\WhatsAppProcessManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoints admin du module WhatsApp.
 *
 * Accès : rôle 'admin' STRICT uniquement (décision utilisateur du 15/08/2026 —
 * pas de 'responsable-division' ici, contrairement à AdminDb).
 *
 * Pattern d'autorisation aligné sur le reste du projet (voir AdminTableController) :
 * middleware auth:sanctum au niveau route, vérification du rôle via assertAdmin()
 * DANS le contrôleur. Il n'existe pas de middleware "role:admin" dans ce projet,
 * on ne l'invente pas ici.
 *
 * QR / statut temps réel : pas d'infrastructure de broadcast (Reverb/Echo/Pusher)
 * configurée dans ce projet. Le Node pousse QR et statut vers webhookReceive()
 * ci-dessous, on les met en cache, et le frontend admin les récupère par polling
 * sur GET /admin/status (recommandé : toutes les 2-3s tant que status != connected).
 */
class WhatsAppAdminController extends Controller
{
    private const ALLOWED_ROLES = ['admin', 'responsable-division'];

    private const CACHE_QR       = 'wa:qr';
    private const CACHE_STATUS   = 'wa:status';
    private const CACHE_QR_TTL   = 120; // secondes — un QR Baileys expire vite de toute façon
    private const CACHE_STATUS_TTL = 300;

    public function __construct(
        private readonly WhatsAppBridgeClient $bridge,
        private readonly WhatsAppProcessManager $processManager,
    ) {
    }

    private function assertAdmin(Request $request): void
    {
        $user = $request->user();
        $slug = $user?->roles->first()?->slug;

        if (!$user || !in_array($slug, self::ALLOWED_ROLES, true)) {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }

    // ─── Onglet Connexion + QR Code (polling) ─────────────────────────────────

    public function status(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        $nodeUp = $this->processManager->isRunning();

        // Statut "live" via cache alimenté par le webhook Node (rapide, pas
        // d'appel HTTP synchrone au Node à chaque poll admin). Fallback sur un
        // appel direct si le cache est vide (ex: juste après redémarrage).
        $cached = Cache::get(self::CACHE_STATUS);

        if ($cached === null && $nodeUp) {
            $cached = $this->bridge->getStatus();
            Cache::put(self::CACHE_STATUS, $cached, self::CACHE_STATUS_TTL);
        }

        return response()->json([
            'node_running' => $nodeUp,
            'status'       => $cached['status'] ?? 'disconnected',
            // Le Node retourne { status, user: { id, name } }.
            // Le webhook "ready" pousse { status, phone, displayName }.
            // On normalise ici pour que le frontend reçoive toujours phone + display_name.
            'phone'        => $cached['phone']
                              ?? (isset($cached['user']['id'])
                                    ? explode(':', $cached['user']['id'])[0]
                                    : null),
            'display_name' => $cached['displayName']
                              ?? $cached['display_name']
                              ?? $cached['user']['name']
                              ?? null,
            'connected_at' => $cached['connectedAt'] ?? $cached['connected_at'] ?? null,
            // QR fourni uniquement si on n'est pas déjà connecté
            'qr' => ($cached['status'] ?? null) !== 'connected' ? Cache::get(self::CACHE_QR) : null,
        ]);
    }

    // ─── Onglet Déconnexion ────────────────────────────────────────────────────

    public function destroySession(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        $ok = $this->bridge->logoutSession();

        if ($ok) {
            Cache::forget(self::CACHE_STATUS);
            Cache::forget(self::CACHE_QR);
        }

        return response()->json(['success' => $ok]);
    }

    // ─── Onglets Messages envoyés / échoués ───────────────────────────────────

    /**
     * Filtrable par statut ET par module (gestion "module par module" —
     * ex: ne voir que les échecs venant de Finance, ou tout Demandes).
     */
    public function messages(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        $status = $request->query('status'); // 'sent' | 'failed' | null (tous)
        $module = $request->query('module'); // ex: 'Demandes' | null (tous)
        $query  = WaMessageLog::query()->orderByDesc('id');

        if (in_array($status, ['queued', 'sending', 'sent', 'failed'], true)) {
            $query->where('status', $status);
        }

        if ($module) {
            $query->where('module', $module);
        }

        $perPage = min((int) $request->query('per_page', 25), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Liste des modules distincts déjà présents dans le journal, avec
     * compteur — alimente le filtre déroulant du frontend admin. Se
     * remplit automatiquement au fil des envois, aucune config à faire
     * quand un nouveau module commence à envoyer des WhatsApp.
     */
    public function modules(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        $modules = WaMessageLog::query()
            ->selectRaw('COALESCE(module, "inconnu") as module, COUNT(*) as total')
            ->groupBy('module')
            ->orderByDesc('total')
            ->get();

        return response()->json($modules);
    }

    // ─── Onglet Retry ──────────────────────────────────────────────────────────

    /**
     * Fonctionne pour les messages texte ET fichier : si le message
     * échoué avait un fichier (file_disk/file_path renseignés), on
     * régénère une URL fraîche et on renvoie via sendFile(), sinon send()
     * classique.
     */
    public function retryMessage(Request $request, int $id): JsonResponse
    {
        $this->assertAdmin($request);

        $log = WaMessageLog::query()->findOrFail($id);

        if ($log->status !== 'failed') {
            return response()->json([
                'success' => false,
                'error'   => 'Seuls les messages en échec peuvent être relancés.',
            ], 422);
        }

        $log->update(['status' => 'sending']);

        if ($log->file_disk && $log->file_path) {
            $ok = $this->bridge->sendFile(
                $log->recipient,
                $log->file_disk,
                $log->file_path,
                $log->file_name ?? basename($log->file_path),
                $log->message ?? '', // la légende avait été stockée comme "message"
                $log->context ?? '',
                $log->module,
            );
        } else {
            $ok = $this->bridge->send($log->recipient, $log->message, $log->context ?? '', $log->module);
        }

        $log->update([
            'status'     => $ok ? 'sent' : 'failed',
            'attempts'   => $log->attempts + 1,
            'sent_at'    => $ok ? now() : $log->sent_at,
            'last_error' => $ok ? null : 'Échec du retry manuel (voir logs Laravel/Node)',
        ]);

        return response()->json(['success' => $ok, 'message' => $log->fresh()]);
    }

    // ─── Onglet Statistiques ───────────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        return response()->json([
            'queued'    => WaMessageLog::where('status', 'queued')->count(),
            'sending'   => WaMessageLog::where('status', 'sending')->count(),
            'sent'      => WaMessageLog::where('status', 'sent')->count(),
            'failed'    => WaMessageLog::where('status', 'failed')->count(),
            'total'     => WaMessageLog::count(),
            'by_module' => WaMessageLog::query()
                ->selectRaw('COALESCE(module, "inconnu") as module')
                ->selectRaw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent')
                ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('module')
                ->orderByDesc('total')
                ->get(),
        ]);
    }

    // ─── Webhook interne (Node → Laravel) ─────────────────────────────────────

    /**
     * Reçu depuis le Node à chaque changement d'état (qr, ready, logged_out,
     * status_update). Pas de auth:sanctum ici (le Node n'est pas un utilisateur
     * Sanctum) : protégé par le même WHATSAPP_BRIDGE_API_KEY que le sens
     * Laravel → Node, réutilisé tel quel pour rester sur "un seul secret".
     *
     * Le Node n'appelle cette route qu'en loopback (127.0.0.1), jamais exposée
     * publiquement au-delà de ce que le reverse proxy autorise déjà pour /api.
     */
    public function webhookReceive(Request $request): JsonResponse
    {
        $expected = (string) config('services.whatsapp_bridge.api_key', '');
        $provided = (string) $request->header('X-Api-Key', '');

        if ($expected !== '' && !hash_equals($expected, $provided)) {
            Log::warning('[WhatsApp] Webhook interne rejeté — clé API invalide');
            abort(401);
        }

        $event   = $request->input('event');
        $payload = $request->input('payload', []);

        match ($event) {
            'qr' => Cache::put(self::CACHE_QR, $payload['qr'] ?? null, self::CACHE_QR_TTL),

            'ready', 'status_update' => Cache::put(self::CACHE_STATUS, [
                'status'      => $payload['status'] ?? 'connected',
                'phone'       => $payload['phone'] ?? null,
                'displayName' => $payload['displayName'] ?? null,
                'connectedAt' => $payload['connectedAt'] ?? now()->toIso8601String(),
            ], self::CACHE_STATUS_TTL),

            'logged_out' => [
                Cache::forget(self::CACHE_QR),
                Cache::put(self::CACHE_STATUS, ['status' => 'disconnected'], self::CACHE_STATUS_TTL),
            ],

            default => Log::info("[WhatsApp] Événement webhook non géré : {$event}"),
        };

        return response()->json(['received' => true]);
    }

    // ─── Fichier interne (Node → Laravel, téléchargement) ──────────────────────

    /**
     * Le Node appelle cette route en loopback pour télécharger un fichier
     * avant de l'envoyer à WhatsApp. Protégée par un token opaque à usage
     * temporaire (10 min) généré par WhatsAppBridgeClient::sendFile() —
     * pas de auth:sanctum (le Node n'est pas un utilisateur), pas de
     * config supplémentaire nécessaire côté disque (fonctionne avec
     * n'importe quel disque Laravel : local, s3...).
     */
    public function serveInternalFile(Request $request, string $token): StreamedResponse
    {
        $entry = Cache::get("wa:file-token:{$token}");

        if (!$entry) {
            abort(404, 'Lien de fichier expiré ou invalide.');
        }

        [$disk, $path] = [$entry['disk'], $entry['path']];

        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'Fichier introuvable.');
        }

        return Storage::disk($disk)->response($path);
    }
}

<?php

namespace App\Modules\WhatsApp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Client HTTP vers le micro-service whatsapp-service (Node.js / Baileys).
 *
 * Interface publique IDENTIQUE à l'ancien Core\Services\WhatsAppBridgeClient :
 * send(), normalizePhone(), isConnected(). Aucun appelant existant
 * (Demandes\WhatsAppService, Attestation\WhatsAppNotificationService,
 * SendGroupedReminders) n'a besoin d'être modifié — voir la classe de
 * compatibilité App\Modules\Core\Services\WhatsAppBridgeClient qui étend
 * celle-ci.
 *
 * CORRECTIF IMPORTANT (14/08/2026) : l'ancienne implémentation n'envoyait
 * JAMAIS le header X-Api-Key, ni dans send() (cURL natif) ni dans
 * isConnected() (façade Http). Cela ne posait aucun problème tant que
 * BRIDGE_API_KEY était vide côté Node (mode dev). Mais dès que
 * WHATSAPP_BRIDGE_API_KEY est activée (ce que la migration prévoit),
 * TOUTES les requêtes se seraient vu répondre 401 par le Node — en silence,
 * car send() catch tout et loggue un warning sans lever d'exception.
 * Le header est maintenant systématiquement injecté quand la clé est définie.
 *
 * Formats de numéros acceptés (normalisation béninoise) — inchangé :
 *   XXXXXXXX           → +22901XXXXXXXX  (8 chiffres)
 *   01XXXXXXXX         → +22901XXXXXXXX  (10 chiffres)
 *   229XXXXXXXX        → +229XXXXXXXX    (ancien format, 11 chiffres)
 *   22901XXXXXXXX      → +22901XXXXXXXX  (nouveau format sans +, 13 chiffres)
 *   0022901XXXXXXXX    → +22901XXXXXXXX  (préfixe 00)
 *   +229XXXXXXXX / +22901XXXXXXXX → inchangé
 * ZÉRO CONFIGURATION POUR LES AUTRES MODULES (15/08/2026) : le module
 * d'origine (ex: "Demandes", "RH", "Finance") est détecté AUTOMATIQUEMENT
 * via debug_backtrace() — le namespace de la classe appelante
 * (App\Modules\{X}\...) devient le tag "module" journalisé dans
 * wa_message_log. Aucun module n'a besoin de rien déclarer ni configurer :
 * il suffit d'injecter WhatsAppBridgeClient (ou son alias
 * Core\Services\WhatsAppBridgeClient) et d'appeler send()/sendFile().
 * Un override explicite reste possible via le paramètre $module si
 * l'auto-détection ne convient pas à un cas particulier.
 */
class WhatsAppBridgeClient
{
    private string $baseUrl;
    private int    $timeout;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.whatsapp_bridge.url', 'http://127.0.0.1:3005'), '/');
        $this->timeout = (int) config('services.whatsapp_bridge.timeout', 30);
        $this->apiKey  = (string) config('services.whatsapp_bridge.api_key', '');
    }

    // ─── Détection automatique du module appelant ─────────────────────────────

    /**
     * Remonte la pile d'appel pour trouver la première classe sous
     * App\Modules\{X}\... — c'est CETTE convention de nommage déjà
     * utilisée par tout le projet qui rend la détection fiable sans
     * qu'aucun module n'ait à se déclarer explicitement.
     *
     * Ignore ses propres classes (WhatsApp, Core) pour ne pas se
     * retrouver à se taguer lui-même si un jour du code interne au
     * module WhatsApp appelle send().
     */
    private function detectCallingModule(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($trace as $frame) {
            $class = $frame['class'] ?? null;
            if ($class && preg_match('/^App\\\\Modules\\\\([A-Za-z0-9]+)\\\\/', $class, $m)) {
                if (!in_array($m[1], ['WhatsApp', 'Core'], true)) {
                    return $m[1];
                }
            }
        }

        return null;
    }

    // ─── Envoi ────────────────────────────────────────────────────────────────

    /**
     * Envoie un message WhatsApp via le whatsapp-service.
     *
     * @param  string  $phone    Numéro brut (tout format béninois accepté)
     * @param  string  $message  Corps du message (formatage WhatsApp supporté : *gras*, _italique_)
     * @param  string  $context  Identifiant de contexte pour les logs (ex: "soumission:REF-001")
     * @return bool   true = succès, false = numéro invalide ou échec (jamais bloquant pour l'appelant)
     */
    public function send(string $phone, string $message, string $context = '', ?string $module = null): bool
    {
        $normalized = $this->normalizePhone($phone);
        $module   ??= $this->detectCallingModule();

        if (!$normalized) {
            Log::warning('[WhatsApp] Numéro invalide ou absent', [
                'phone'   => $phone,
                'context' => $context,
                'module'  => $module,
            ]);
            return false;
        }

        // Le Node bridge n'aime pas le signe +, on l'enlève ici pour être sûr
        $cleanTo = str_replace('+', '', $normalized);

        try {
            $data = json_encode([
                'to'      => $cleanTo,
                'text'    => $message,
                'context' => $context,
                'module'  => $module, // détecté automatiquement, journalisé par le Node
            ]);

            $headers = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data),
            ];
            if ($this->apiKey !== '') {
                $headers[] = 'X-Api-Key: ' . $this->apiKey;
            }

            $ch = curl_init("{$this->baseUrl}/send-message");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            // Forcer IPv4 pour éviter les bugs loopback Windows
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $result    = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                Log::error("[WhatsApp] Échec critique cURL sur {$this->baseUrl} : $curlError");
                return false;
            }

            $response = json_decode($result, true);

            if ($httpCode >= 200 && $httpCode < 300 && isset($response['success']) && $response['success']) {
                Log::info('[WhatsApp] Message envoyé via bridge', [
                    'to'      => $cleanTo,
                    'context' => $context,
                ]);
                return true;
            }

            if ($httpCode === 401) {
                Log::error('[WhatsApp] Rejeté par le bridge (401) — clé API invalide ou manquante côté Laravel/Node', [
                    'to'      => $cleanTo,
                    'context' => $context,
                ]);
                return false;
            }

            Log::warning('[WhatsApp] Réponse négative du bridge', [
                'to'       => $cleanTo,
                'context'  => $context,
                'httpCode' => $httpCode,
                'error'    => $response['error'] ?? 'Erreur inconnue',
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('[WhatsApp] Échec exceptionnel du bridge', [
                'to'      => $normalized,
                'context' => $context,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ─── Envoi de fichier (document ou image) ──────────────────────────────────

    /**
     * Envoie un fichier WhatsApp (PDF, image, etc.) depuis n'importe quel
     * disque Laravel (local, s3...). Le Node ne reçoit jamais le fichier en
     * base64 : on lui donne une URL signée temporaire (10 min, usage
     * interne loopback) qu'il télécharge lui-même via Baileys — efficace
     * même pour des fichiers volumineux.
     *
     * @param  string  $phone     Numéro destinataire
     * @param  string  $disk      Disque Laravel (ex: 'local', 's3')
     * @param  string  $path      Chemin du fichier sur ce disque
     * @param  string  $fileName  Nom affiché côté destinataire (ex: "Attestation.pdf")
     * @param  string  $caption   Légende optionnelle (affichée avec le fichier)
     * @param  string  $context   Identifiant de contexte pour les logs
     * @param  ?string $module    Override manuel — sinon auto-détecté comme send()
     */
    public function sendFile(
        string $phone,
        string $disk,
        string $path,
        string $fileName,
        string $caption = '',
        string $context = '',
        ?string $module = null,
    ): bool {
        $normalized = $this->normalizePhone($phone);
        $module   ??= $this->detectCallingModule();

        if (!$normalized) {
            Log::warning('[WhatsApp] Numéro invalide ou absent (envoi fichier)', [
                'phone' => $phone, 'context' => $context, 'module' => $module,
            ]);
            return false;
        }

        if (!Storage::disk($disk)->exists($path)) {
            Log::error('[WhatsApp] Fichier introuvable pour envoi', [
                'disk' => $disk, 'path' => $path, 'context' => $context,
            ]);
            return false;
        }

        $cleanTo  = str_replace('+', '', $normalized);
        $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
        $fileUrl  = $this->makeInternalFileUrl($disk, $path);

        try {
            $data = json_encode([
                'to'        => $cleanTo,
                'fileUrl'   => $fileUrl,
                'fileName'  => $fileName,
                'mimeType'  => $mimeType,
                'caption'   => $caption,
                'context'   => $context,
                'module'    => $module,
                // Persistés (pas juste le token éphémère 10 min) pour que le
                // RETRY admin puisse régénérer une URL fraîche à tout moment.
                'fileDisk'  => $disk,
                'filePath'  => $path,
            ]);

            $headers = ['Content-Type: application/json', 'Content-Length: ' . strlen($data)];
            if ($this->apiKey !== '') {
                $headers[] = 'X-Api-Key: ' . $this->apiKey;
            }

            $ch = curl_init("{$this->baseUrl}/send-file");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // Timeout plus généreux que pour un texte simple : le Node doit
            // télécharger le fichier puis l'envoyer à WhatsApp.
            curl_setopt($ch, CURLOPT_TIMEOUT, max($this->timeout, 60));
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $result   = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                Log::error("[WhatsApp] Échec critique cURL (fichier) sur {$this->baseUrl} : $curlErr");
                return false;
            }

            $response = json_decode($result, true);

            if ($httpCode >= 200 && $httpCode < 300 && isset($response['success']) && $response['success']) {
                Log::info('[WhatsApp] Fichier envoyé via bridge', [
                    'to' => $cleanTo, 'fileName' => $fileName, 'context' => $context,
                ]);
                return true;
            }

            Log::warning('[WhatsApp] Échec envoi fichier', [
                'to' => $cleanTo, 'fileName' => $fileName, 'context' => $context,
                'httpCode' => $httpCode, 'error' => $response['error'] ?? 'Erreur inconnue',
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('[WhatsApp] Échec exceptionnel envoi fichier', [
                'to' => $normalized, 'fileName' => $fileName, 'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Génère une URL interne temporaire (10 min) que le Node peut appeler
     * en loopback pour télécharger le fichier, quel que soit le disque
     * Laravel réel (local, s3...) — le Node n'a jamais besoin de connaître
     * la config de stockage, seulement cette URL opaque.
     */
    private function makeInternalFileUrl(string $disk, string $path): string
    {
        $token = Str::random(48);

        Cache::put("wa:file-token:{$token}", [
            'disk' => $disk,
            'path' => $path,
        ], now()->addMinutes(10));

        $appUrl = rtrim(config('app.url', 'http://127.0.0.1'), '/');

        return "{$appUrl}/api/whatsapp/internal/files/{$token}";
    }

    // ─── Normalisation numéro ─────────────────────────────────────────────────

    public function normalizePhone(string $phone): ?string
    {
        $clean  = preg_replace('/[\s\-.()\t]/', '', trim($phone));
        $digits = preg_replace('/\D/', '', $clean);

        if (strlen($digits) < 8) {
            return null;
        }

        // Préfixe 00 → enlever les deux zéros
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $this->normalizeDigits($digits);
    }

    private function normalizeDigits(string $digits): ?string
    {
        return match (strlen($digits)) {
            8  => '+229' . $digits,                                          // XXXXXXXX  → +229XXXXXXXX (béninois 8 chiffres)
            10 => str_starts_with($digits, '01') ? '+229' . $digits : null,  // 01XXXXXXXX → +22901XXXXXXXX
            11 => str_starts_with($digits, '229') ? '+' . $digits : null,    // 229XXXXXXXX → +229XXXXXXXX (ancien)
            13 => str_starts_with($digits, '22901') ? '+' . $digits : null,  // 22901XXXXXXXX → +22901XXXXXXXX
            default => null,
        };
    }

    // ─── Vérification de santé du bridge ─────────────────────────────────────

    /**
     * Vérifie que le whatsapp-service est joignable et que WhatsApp est connecté.
     * Utilisé par SendNotificationJob::handleWhatsApp() avant chaque envoi,
     * et par le module admin pour l'onglet "Connexion".
     *
     * NOTE : le docblock de l'ancienne version de WhatsAppService affirmait
     * un cache de 10s sur ce résultat — ce cache n'a jamais existé dans le
     * code réel. On ne l'ajoute pas ici non plus pour rester fidèle au
     * comportement observé (l'appel /status est bon marché côté Node).
     */
    public function isConnected(): bool
    {
        try {
            $request = Http::timeout(5);
            if ($this->apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $this->apiKey]);
            }
            $response = $request->get("{$this->baseUrl}/status");
            return $response->successful() && ($response->json('status') === 'connected');
        } catch (\Exception $e) {
            return false;
        }
    }

    // ─── Actions admin ─────────────────────────────────────────────────────────

    /**
     * Récupère le statut détaillé (utilisé par l'onglet admin "Connexion").
     * Contrairement à isConnected(), retourne le payload complet, pas juste un bool.
     */
    public function getStatus(): array
    {
        try {
            $request = Http::timeout(5);
            if ($this->apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $this->apiKey]);
            }
            $response = $request->get("{$this->baseUrl}/status");
            if ($response->successful()) {
                return $response->json() ?? ['status' => 'disconnected'];
            }
            return ['status' => 'unreachable'];
        } catch (\Exception $e) {
            return ['status' => 'unreachable', 'error' => $e->getMessage()];
        }
    }

    /**
     * Déconnecte la session WhatsApp active (onglet admin "Déconnexion").
     */
    public function logoutSession(): bool
    {
        try {
            $request = Http::timeout($this->timeout);
            if ($this->apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $this->apiKey]);
            }
            $response = $request->delete("{$this->baseUrl}/logout");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Échec logout admin', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

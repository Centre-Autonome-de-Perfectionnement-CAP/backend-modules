<?php

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP vers le micro-service whatsapp-service (Node.js / Baileys).
 *
 * Ce client remplace l'ancienne intégration Twilio.
 * Il centralise tous les appels vers le endpoint POST /send-message du bridge.
 *
 * Configuration (config/services.php) :
 *   services.whatsapp_bridge.url  →  URL du service (ex: http://localhost:3000)
 *   services.whatsapp_bridge.timeout → timeout HTTP en secondes (défaut : 10)
 *
 * Formats de numéros acceptés (normalisation béninoise) :
 *   XXXXXXXX           → +22901XXXXXXXX  (8 chiffres)
 *   01XXXXXXXX         → +22901XXXXXXXX  (10 chiffres)
 *   229XXXXXXXX        → +229XXXXXXXX    (ancien format, 11 chiffres)
 *   22901XXXXXXXX      → +22901XXXXXXXX  (nouveau format sans +, 13 chiffres)
 *   0022901XXXXXXXX    → +22901XXXXXXXX  (préfixe 00)
 *   +229XXXXXXXX       → inchangé
 *   +22901XXXXXXXX     → inchangé
 *   Séparateurs (espaces, tirets, points) tolérés.
 */
class WhatsAppBridgeClient
{
    private string $baseUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.whatsapp_bridge.url', 'http://localhost:3000'), '/');
        $this->timeout = (int) config('services.whatsapp_bridge.timeout', 30);
    }

    // ─── Envoi ────────────────────────────────────────────────────────────────

    /**
     * Envoie un message WhatsApp via le whatsapp-service.
     *
     * @param  string  $phone    Numéro brut (tout format béninois accepté)
     * @param  string  $message  Corps du message (formatage WhatsApp supporté : *gras*, _italique_)
     * @param  string  $context  Identifiant de contexte pour les logs (ex: "soumission:REF-001")
     * @return bool   true = succès ou service indisponible (non bloquant), false = numéro invalide
     */
    public function send(string $phone, string $message, string $context = ''): bool
    {
        $normalized = $this->normalizePhone($phone);

        if (!$normalized) {
            Log::warning('[WhatsApp] Numéro invalide ou absent', [
                'phone'   => $phone,
                'context' => $context,
            ]);
            return false;
        }

        // Le Node bridge n'aime pas le signe +, on l'enlève ici pour être sûr
        $cleanTo = str_replace('+', '', $normalized);

        try {
            $data = json_encode([
                'to'   => $cleanTo,
                'text' => $message,
            ]);

            $ch = curl_init("{$this->baseUrl}/send-message");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            
            // Forcer IPv4 pour éviter les bugs loopback Windows
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                Log::error("[WhatsApp] Échec critique cURL sur {$this->baseUrl} : $curlError");
                return false;
            }

            $response = json_decode($result, true);
            
            if ($httpCode >= 200 && $httpCode < 300 && isset($response['success']) && $response['success']) {
                Log::info('[WhatsApp] Message envoyé via bridge (cURL natif)', [
                    'to'      => $cleanTo,
                    'context' => $context,
                ]);
                return true;
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
     * Utile pour les checks de santé ou les commandes artisan de diagnostic.
     */
    public function isConnected(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/status");
            return $response->successful() && ($response->json('status') === 'connected');
        } catch (\Exception $e) {
            return false;
        }
    }
}

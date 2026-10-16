<?php

namespace App\Modules\Attestation\Services;

use App\Modules\Core\Services\Notifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Validation par code (WhatsApp + email) avant le dépôt d'un complément de
 * dossier.
 *
 * Avant : la référence de la demande suffisait pour déposer des pièces dans
 * le dossier d'un étudiant. Désormais, un code à 6 chiffres est envoyé au
 * numéro WhatsApp ENREGISTRÉ dans la demande (et, en secours, à son email
 * enregistré) : seul le demandeur peut compléter son dossier.
 *
 *  - code valable 10 min, 5 essais, nouvel envoi possible après 60 s,
 *    5 envois maximum par heure et par dossier ;
 *  - code vérifié → jeton à usage unique valable 30 min pour CETTE référence.
 */
class ComplementOtpService
{
    private const CODE_TTL_MIN    = 10;
    private const TOKEN_TTL_MIN   = 30;
    private const MAX_ATTEMPTS    = 5;
    private const RESEND_COOLDOWN = 60;
    private const MAX_SENDS_PER_HOUR = 5;

    public function __construct(private readonly Notifier $notifier) {}

    /**
     * @return array{sent: bool, retry_after?: int, channels: string[], whatsapp_hint: ?string, email_hint: ?string}
     */
    public function send(object $demande): array
    {
        $reference = strtoupper($demande->reference);
        $key       = $this->codeKey($reference);
        $existing  = Cache::get($key);

        if ($existing && (now()->timestamp - $existing['sent_at']) < self::RESEND_COOLDOWN) {
            return [
                'sent'          => false,
                'retry_after'   => self::RESEND_COOLDOWN - (now()->timestamp - $existing['sent_at']),
                'channels'      => $existing['channels'],
                'whatsapp_hint' => $this->maskPhone($demande->demandeur_whatsapp ?? null),
                'email_hint'    => $this->maskEmail($demande->email ?? null),
            ];
        }

        // Anti-harcèlement : 5 envois maximum par heure et par dossier.
        $hourKey = "complement-otp-sends:{$reference}:" . now()->format('YmdH');
        Cache::add($hourKey, 0, now()->addHour());
        if (Cache::increment($hourKey) > self::MAX_SENDS_PER_HOUR) {
            return [
                'sent'          => false,
                'retry_after'   => 3600 - (now()->minute * 60 + now()->second),
                'channels'      => [],
                'whatsapp_hint' => $this->maskPhone($demande->demandeur_whatsapp ?? null),
                'email_hint'    => $this->maskEmail($demande->email ?? null),
            ];
        }

        $code     = (string) random_int(100000, 999999);
        $channels = [];

        if (!empty($demande->demandeur_whatsapp)) {
            $this->notifier->whatsapp(
                $demande->demandeur_whatsapp,
                "*CAP-EPAC — Code de vérification*\nVotre code pour compléter le dossier *{$reference}* : *{$code}*\n"
                    . "Valable " . self::CODE_TTL_MIN . " minutes. Ne le communiquez à personne.",
                "complement-otp:{$reference}",
                'Attestation',
            );
            $channels[] = 'whatsapp';
        }

        if (!empty($demande->email)) {
            $this->notifier->email($demande->email, "Code de vérification — dossier {$reference}", 'core::emails.otp-verification', [
                'code'       => $code,
                'email'      => $demande->email,
                'ttlMinutes' => self::CODE_TTL_MIN,
            ]);
            $channels[] = 'email';
        }

        Cache::put($key, [
            'hash'     => hash('sha256', $code),
            'attempts' => 0,
            'sent_at'  => now()->timestamp,
            'channels' => $channels,
        ], now()->addMinutes(self::CODE_TTL_MIN));

        return [
            'sent'          => !empty($channels),
            'channels'      => $channels,
            'whatsapp_hint' => $this->maskPhone($demande->demandeur_whatsapp ?? null),
            'email_hint'    => $this->maskEmail($demande->email ?? null),
        ];
    }

    /**
     * @return array{ok: bool, token?: string, message?: string}
     */
    public function verify(string $reference, string $code): array
    {
        $reference = strtoupper(trim($reference));
        $key       = $this->codeKey($reference);
        $stored    = Cache::get($key);

        if (!$stored) {
            return ['ok' => false, 'message' => 'Code expiré ou inexistant. Demandez un nouveau code.'];
        }

        if ($stored['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
            return ['ok' => false, 'message' => 'Trop de tentatives. Demandez un nouveau code.'];
        }

        if (!hash_equals($stored['hash'], hash('sha256', trim($code)))) {
            $stored['attempts']++;
            Cache::put($key, $stored, now()->addMinutes(self::CODE_TTL_MIN));
            return ['ok' => false, 'message' => 'Code incorrect.'];
        }

        Cache::forget($key);
        $token = 'ct_' . Str::random(40);
        Cache::put($this->tokenKey($token), $reference, now()->addMinutes(self::TOKEN_TTL_MIN));

        return ['ok' => true, 'token' => $token];
    }

    /** Vérifie puis consomme le jeton (usage unique). */
    public function consumeToken(?string $token, string $reference): bool
    {
        if (!$token) {
            return false;
        }

        $stored = Cache::pull($this->tokenKey($token));

        return $stored !== null && hash_equals($stored, strtoupper(trim($reference)));
    }

    /** Remet le jeton (ex. dépôt refusé pour une raison non liée au code). */
    public function restoreToken(string $token, string $reference): void
    {
        Cache::put($this->tokenKey($token), strtoupper(trim($reference)), now()->addMinutes(self::TOKEN_TTL_MIN));
    }

    private function codeKey(string $reference): string
    {
        return 'complement-otp:' . $reference;
    }

    private function tokenKey(string $token): string
    {
        return 'complement-otp-token:' . hash('sha256', $token);
    }

    private function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        return strlen($digits) >= 4 ? str_repeat('•', max(0, strlen($digits) - 4)) . substr($digits, -4) : null;
    }

    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return null;
        }
        [$user, $domain] = explode('@', $email, 2);
        return mb_substr($user, 0, 2) . str_repeat('•', max(1, mb_strlen($user) - 2)) . '@' . $domain;
    }
}

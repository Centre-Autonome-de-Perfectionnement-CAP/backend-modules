<?php

namespace App\Modules\Stages\Services;

use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Accès du demandeur à sa demande de stage : référence + code à 6 chiffres
 * envoyé à SON numéro WhatsApp et à SON email enregistrés.
 *
 *  - code valable 10 min, 5 essais, nouvel envoi après 60 s,
 *    5 envois maximum par heure et par demande ;
 *  - code vérifié → jeton de session valable 30 min pour CETTE référence
 *    (consultation + réponse à une proposition).
 */
class StageSuiviOtpService
{
    private const CODE_TTL_MIN       = 10;
    private const TOKEN_TTL_MIN      = 30;
    private const MAX_ATTEMPTS       = 5;
    private const RESEND_COOLDOWN    = 60;
    private const MAX_SENDS_PER_HOUR = 5;

    public function __construct(private readonly Notifier $notifier) {}

    public function send(StageRequest $req): array
    {
        $reference = $req->reference;
        $key       = $this->codeKey($reference);
        $existing  = Cache::get($key);
        $hints     = ['whatsapp_hint' => $this->maskPhone($req->whatsapp), 'email_hint' => $this->maskEmail($req->email)];

        if ($existing && (now()->timestamp - $existing['sent_at']) < self::RESEND_COOLDOWN) {
            return ['sent' => false, 'retry_after' => self::RESEND_COOLDOWN - (now()->timestamp - $existing['sent_at'])] + $hints;
        }

        $hourKey = "stage-otp-sends:{$reference}:" . now()->format('YmdH');
        Cache::add($hourKey, 0, now()->addHour());
        if (Cache::increment($hourKey) > self::MAX_SENDS_PER_HOUR) {
            return ['sent' => false, 'retry_after' => 3600 - (now()->minute * 60 + now()->second)] + $hints;
        }

        $code = (string) random_int(100000, 999999);

        $this->notifier->whatsapp(
            $req->whatsapp,
            WhatsAppText::compose('Code de vérification', [
                "Votre code pour consulter la demande de stage *{$reference}* :",
                "*{$code}*",
                '_Valable ' . self::CODE_TTL_MIN . ' minutes. Ne le communiquez à personne._',
            ]),
            "stage-otp:{$reference}",
            'Stages',
        );
        $this->notifier->email($req->email, "Code de vérification — {$reference}", 'core::emails.otp-verification', [
            'code' => $code, 'email' => $req->email, 'ttlMinutes' => self::CODE_TTL_MIN,
        ]);

        Cache::put($key, [
            'hash' => hash('sha256', $code), 'attempts' => 0, 'sent_at' => now()->timestamp,
        ], now()->addMinutes(self::CODE_TTL_MIN));

        return ['sent' => true] + $hints;
    }

    /** @return array{ok: bool, token?: string, message?: string} */
    public function verify(string $reference, string $code): array
    {
        $key    = $this->codeKey($reference);
        $stored = Cache::get($key);

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
        $token = 'st_' . Str::random(48);
        Cache::put($this->tokenKey($token), $reference, now()->addMinutes(self::TOKEN_TTL_MIN));

        return ['ok' => true, 'token' => $token, 'expires_in' => self::TOKEN_TTL_MIN * 60];
    }

    public function check(?string $token, string $reference): bool
    {
        if (!$token) {
            return false;
        }
        $stored = Cache::get($this->tokenKey($token));

        return is_string($stored) && hash_equals($stored, $reference);
    }

    private function codeKey(string $reference): string
    {
        return 'stage-otp:' . $reference;
    }

    private function tokenKey(string $token): string
    {
        return 'stage-otp-token:' . hash('sha256', $token);
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

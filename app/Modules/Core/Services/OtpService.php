<?php

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Durée de validité du code OTP en minutes
     */
    private const OTP_TTL_MINUTES = 15;

    /**
     * Durée de validité du jeton de vérification en minutes
     */
    private const TOKEN_TTL_MINUTES = 30;

    /**
     * Génère et envoie un code OTP à 6 chiffres par email
     */
    public function sendOtp(string $email, string $purpose = 'general', ?string $matricule = null): array
    {
        $normalizedEmail = trim(mb_strtolower($email));
        
        // Générer un code à 6 chiffres (100000 - 999999)
        $code = (string) random_int(100000, 999999);

        // Clé de cache
        $cacheKey = 'otp_' . md5($normalizedEmail . '_' . $purpose);
        Cache::put($cacheKey, [
            'code' => $code,
            'email' => $normalizedEmail,
            'purpose' => $purpose,
            'matricule' => $matricule,
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        // Envoi par email, asynchrone (file "default", sans délai : le code
        // arrive en quelques secondes sans bloquer la réponse HTTP).
        app(Notifier::class)->email(
            $normalizedEmail,
            'Code de vérification - CAP EPAC',
            'core::emails.otp-verification',
            ['code' => $code, 'email' => $normalizedEmail, 'ttlMinutes' => self::OTP_TTL_MINUTES],
        );

        return [
            'success' => true,
            'message' => 'Code de vérification envoyé avec succès.',
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ];
    }

    /**
     * Vérifie le code OTP et génère un token de vérification
     */
    public function verifyOtp(string $email, string $code, string $purpose = 'general'): array
    {
        $normalizedEmail = trim(mb_strtolower($email));
        $cacheKey = 'otp_' . md5($normalizedEmail . '_' . $purpose);

        $stored = Cache::get($cacheKey);

        if (!$stored) {
            return [
                'success' => false,
                'verified' => false,
                'message' => 'Le code de vérification a expiré ou est invalide.',
            ];
        }

        // Vérifier le nombre d'essais
        if (($stored['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);
            return [
                'success' => false,
                'verified' => false,
                'message' => 'Trop de tentatives infructueuses. Veuillez demander un nouveau code.',
            ];
        }

        if (trim($code) !== trim($stored['code'])) {
            $stored['attempts'] = ($stored['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $stored, now()->addMinutes(self::OTP_TTL_MINUTES));

            return [
                'success' => false,
                'verified' => false,
                'message' => 'Code de vérification incorrect.',
            ];
        }

        // Code correct : consommer le code OTP
        Cache::forget($cacheKey);

        // Créer un token de vérification à usage unique
        $verificationToken = 'vt_' . Str::random(40);
        $tokenKey = 'otp_verified_' . $verificationToken;
        Cache::put($tokenKey, [
            'email' => $normalizedEmail,
            'purpose' => $purpose,
            'verified_at' => now()->timestamp,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return [
            'success' => true,
            'verified' => true,
            'verification_token' => $verificationToken,
            'message' => 'Vérification réussie.',
        ];
    }

    /**
     * Valide qu'un verification_token est valide pour un email donné
     */
    public function validateToken(string $verificationToken, string $email): bool
    {
        $tokenKey = 'otp_verified_' . $verificationToken;
        $stored = Cache::get($tokenKey);

        if (!$stored) {
            return false;
        }

        $normalizedEmail = trim(mb_strtolower($email));
        return $stored['email'] === $normalizedEmail;
    }
}

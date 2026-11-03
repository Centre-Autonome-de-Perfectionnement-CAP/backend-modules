<?php

namespace App\Modules\Core\Support;

/**
 * Durée de validité des codes à usage unique (OTP) envoyés par WhatsApp ou
 * par email : une journée, pour qu'un code reçu en retard (réseau, email
 * filtré) reste utilisable. Chaque code reste limité en nombre d'essais.
 */
final class OtpTtl
{
    public const MINUTES = 1440;

    /** « 24 heures », « 15 minutes »… pour les messages. */
    public static function label(int $minutes = self::MINUTES): string
    {
        if ($minutes % 60 === 0) {
            $h = intdiv($minutes, 60);

            return $h . ' heure' . ($h > 1 ? 's' : '');
        }

        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}

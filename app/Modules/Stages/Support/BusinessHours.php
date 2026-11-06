<?php

namespace App\Modules\Stages\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Heures de réception des notifications pour le personnel (responsable de
 * division, superviseurs, secrétariat…) :
 *   - du lundi au vendredi, de 07h30 à 18h30 (heure du Bénin) ;
 *   - en dehors (nuit, samedi, dimanche), la notification est reportée au
 *     prochain créneau ouvré à 07h30.
 * Demandeurs et stagiaires : tous les jours, sauf la nuit (21h00 - 07h00) ;
 * les codes de vérification et alertes de sécurité partent à toute heure.
 */
final class BusinessHours
{
    public const TZ = 'Africa/Porto-Novo';
    private const OPEN  = [7, 30];
    private const CLOSE = [18, 30];
    private const QUIET_FROM = 21;
    private const QUIET_TO   = 7;

    /** Moment où un message à un demandeur ou stagiaire peut partir (null = maintenant). */
    public static function nextInternSlot(?CarbonInterface $now = null): ?CarbonImmutable
    {
        $local = CarbonImmutable::instance($now ?? now())->setTimezone(self::TZ);
        if ($local->hour >= self::QUIET_TO && $local->hour < self::QUIET_FROM) {
            return null;
        }
        $at = $local->setTime(self::QUIET_TO, 0);

        return $local->hour >= self::QUIET_FROM ? $at->addDay() : $at;
    }

    /** Moment où une notification au personnel peut partir (null = maintenant). */
    public static function nextStaffSlot(?CarbonInterface $now = null): ?CarbonImmutable
    {
        $local = CarbonImmutable::instance($now ?? now())->setTimezone(self::TZ);

        if (self::isOpen($local)) {
            return null;
        }

        $candidate = $local->setTime(...self::OPEN);
        if ($local->greaterThanOrEqualTo($local->setTime(...self::CLOSE))) {
            $candidate = $candidate->addDay();
        }
        while ($candidate->isWeekend()) {
            $candidate = $candidate->addDay();
        }

        return $candidate;
    }

    public static function isOpen(CarbonInterface $at): bool
    {
        $local = CarbonImmutable::instance($at)->setTimezone(self::TZ);

        return !$local->isWeekend()
            && $local->greaterThanOrEqualTo($local->setTime(...self::OPEN))
            && $local->lessThan($local->setTime(...self::CLOSE));
    }

    /** Aujourd'hui (date du Bénin). */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TZ)->startOfDay();
    }
}

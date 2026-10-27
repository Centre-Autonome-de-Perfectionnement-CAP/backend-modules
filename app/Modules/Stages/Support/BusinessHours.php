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
 * Les demandeurs et stagiaires, eux, sont notifiés à tout moment.
 */
final class BusinessHours
{
    public const TZ = 'Africa/Porto-Novo';
    private const OPEN  = [7, 30];
    private const CLOSE = [18, 30];

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

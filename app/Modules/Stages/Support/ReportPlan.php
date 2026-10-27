<?php

namespace App\Modules\Stages\Support;

use Carbon\CarbonImmutable;

/**
 * Calendrier des rapports d'un stagiaire.
 *
 *  - périodiques (journalier, hebdomadaire - par défaut —, mensuel), du
 *    début réel du stage jusqu'à l'ouverture du rapport final ;
 *  - dépôt ouvert le dernier jour ouvré de la période, fermé N jours ouvrés
 *    après la fin de la période (au-delà : accepté, marqué « en retard ») ;
 *  - rapport final : ouvert N jours avant la fin du stage, attendu au plus
 *    tard le dernier jour.
 */
final class ReportPlan
{
    public const FREQUENCIES = ['journalier', 'hebdomadaire', 'mensuel'];
    public const FINAL_KEY = 'FINAL';

    /**
     * @return array<int, array{key: string, kind: string, label: string, start: string, end: string, opens: string, closes: string}>
     */
    public static function periods(CarbonImmutable $start, CarbonImmutable $end, string $frequency, int $graceDays, int $finalOpenDays): array
    {
        $finalOpens = $end->subDays(max(0, $finalOpenDays))->max($start);
        $out = [];
        $cursor = $start;
        $guard = 0;

        while ($cursor->lessThan($finalOpens) && $guard++ < 400) {
            [$pStart, $pEnd, $key, $label] = match ($frequency) {
                'journalier' => [$cursor, $cursor, 'J' . $cursor->toDateString(), $cursor->locale('fr')->isoFormat('dddd D MMMM')],
                'mensuel'    => [$cursor, $cursor->endOfMonth()->startOfDay(), 'M' . $cursor->format('Y-m'), ucfirst($cursor->locale('fr')->isoFormat('MMMM YYYY'))],
                default      => [$cursor, $cursor->startOfWeek(CarbonImmutable::MONDAY)->addDays(4), 'S' . $cursor->isoFormat('GGGG-[W]WW'),
                                 'Semaine du ' . $cursor->startOfWeek(CarbonImmutable::MONDAY)->locale('fr')->isoFormat('D MMMM')],
            };
            $pEnd = $pEnd->min($end);
            $next = match ($frequency) {
                'journalier' => $cursor->addDay(),
                'mensuel'    => $cursor->startOfMonth()->addMonth(),
                default      => $cursor->startOfWeek(CarbonImmutable::MONDAY)->addWeek(),
            };
            $cursor = $next;

            if ($frequency === 'journalier' && $pStart->isWeekend()) {
                continue;
            }
            $lastWorking = self::lastWorkingDay($pStart, $pEnd);
            if ($lastWorking === null) {
                continue;
            }

            $out[] = [
                'key'    => $key,
                'kind'   => 'periodique',
                'label'  => $label,
                'start'  => $pStart->toDateString(),
                'end'    => $pEnd->toDateString(),
                'opens'  => $lastWorking->toDateString(),
                'closes' => self::addWorkingDays($pEnd, max(0, $graceDays))->toDateString(),
            ];
        }

        $out[] = [
            'key'    => self::FINAL_KEY,
            'kind'   => 'final',
            'label'  => 'Rapport final',
            'start'  => $start->toDateString(),
            'end'    => $end->toDateString(),
            'opens'  => $finalOpens->toDateString(),
            'closes' => $end->toDateString(),
        ];

        return $out;
    }

    public static function addWorkingDays(CarbonImmutable $from, int $n): CarbonImmutable
    {
        $d = $from;
        while ($n > 0) {
            $d = $d->addDay();
            if (!$d->isWeekend()) {
                $n--;
            }
        }

        return $d;
    }

    private static function lastWorkingDay(CarbonImmutable $start, CarbonImmutable $end): ?CarbonImmutable
    {
        for ($d = $end; $d->greaterThanOrEqualTo($start); $d = $d->subDay()) {
            if (!$d->isWeekend()) {
                return $d;
            }
        }

        return null;
    }
}

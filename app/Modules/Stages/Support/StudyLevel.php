<?php

namespace App\Modules\Stages\Support;

use Illuminate\Validation\Rule;

/**
 * Niveau d'études : un cycle (liste) et une année dans ce cycle (liste), la
 * filière restant libre. Le cycle ingénieur se compte directement en années
 * (« 1re année du cycle ingénieur ») : pas de L1/L2.
 * Le libellé complet (« Licence 3 Génie informatique ») est gardé dans
 * filiere_niveau, lu par les documents et les exports.
 */
final class StudyLevel
{
    /** cycle => [libellé, nombre d'années (0 : sans année)] */
    public const CYCLES = [
        'licence'   => ['Licence', 3],
        'master'    => ['Master', 2],
        'ingenieur' => ['Cycle ingénieur', 5],
        'bts'       => ['BTS', 2],
        'doctorat'  => ['Doctorat', 3],
        'autre'     => ['Autre', 0],
    ];

    /** Règles de validation (l'année dépend du cycle choisi). */
    public static function rules(array $input): array
    {
        $years = self::CYCLES[$input['cycle'] ?? ''][1] ?? 0;

        return [
            'filiere'     => ['required', 'string', 'min:2', 'max:120'],
            'cycle'       => ['required', 'string', Rule::in(array_keys(self::CYCLES))],
            'annee_etude' => $years ? ['required', 'integer', 'min:1', 'max:' . $years] : ['nullable', 'prohibited'],
        ];
    }

    public const MESSAGES = [
        'filiere.required'     => 'Indiquez votre filière.',
        'cycle.required'       => 'Choisissez votre niveau d\'études.',
        'cycle.in'             => 'Niveau d\'études invalide.',
        'annee_etude.required' => 'Choisissez votre année d\'études.',
        'annee_etude.max'      => 'Année d\'études invalide pour ce niveau.',
    ];

    /** « Licence 3 », « Master 2 », « 1re année du cycle ingénieur », « BTS 2e année ». */
    public static function level(?string $cycle, ?int $year): string
    {
        if (!$cycle || !isset(self::CYCLES[$cycle]) || $cycle === 'autre') {
            return '';
        }
        $nth = $year ? ($year === 1 ? '1re' : "{$year}e") : '';

        return match ($cycle) {
            'licence', 'master' => trim(self::CYCLES[$cycle][0] . ' ' . ($year ?: '')),
            'ingenieur'         => $nth ? "{$nth} année du cycle ingénieur" : 'Cycle ingénieur',
            default             => trim(self::CYCLES[$cycle][0] . ($nth ? " {$nth} année" : '')),
        };
    }

    /** Libellé complet : niveau puis filière. */
    public static function formation(?string $filiere, ?string $cycle, ?int $year): string
    {
        return trim(self::level($cycle, $year) . ' ' . trim((string) $filiere));
    }

    /**
     * Ancienne saisie libre (« L3 Informatique », « Master 1 GL »…) : cycle et
     * année reconnus si possible, sinon « autre » avec le texte d'origine.
     *
     * @return array{filiere: string, cycle: string, annee_etude: ?int}
     */
    public static function guess(string $raw): array
    {
        $raw = trim(preg_replace('/\s+/u', ' ', $raw));
        $patterns = [
            'licence'   => '/\b(?:licence|license|lic\.?|l)\s*-?\s*([1-3])\b/iu',
            'master'    => '/\b(?:master|m)\s*-?\s*([12])\b/iu',
            'ingenieur' => '/\b(?:ing[ée]nieur|ing\.?)\s*-?\s*([1-5])\b|\b([1-5])\s*(?:e|è|ème|eme|re|ère)?\s*ann[ée]e\s+(?:du\s+)?cycle\s+ing[ée]nieur/iu',
            'bts'       => '/\bbts\s*-?\s*([12])\b/iu',
            'doctorat'  => '/\b(?:doctorat|d)\s*-?\s*([1-3])\b/iu',
        ];
        foreach ($patterns as $cycle => $re) {
            if (preg_match($re, $raw, $m)) {
                $year    = (int) ($m[1] ?: ($m[2] ?? 0));
                $filiere = trim(preg_replace($re, '', $raw, 1), " -–,/");

                return ['filiere' => $filiere, 'cycle' => $cycle, 'annee_etude' => $year ?: null];
            }
        }

        return ['filiere' => $raw, 'cycle' => 'autre', 'annee_etude' => null];
    }
}

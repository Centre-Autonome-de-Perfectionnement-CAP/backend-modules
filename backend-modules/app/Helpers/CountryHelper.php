<?php

namespace App\Helpers;

class CountryHelper
{
    /**
     * Liste des pays membres de l'UEMOA
     */
    const UEMOA_COUNTRIES = [
        'Bénin',
        'Burkina Faso',
        'Côte d\'Ivoire',
        'Guinée-Bissau',
        'Mali',
        'Niger',
        'Sénégal',
        'Togo',
    ];

    /**
     * Vérifie si un pays fait partie de l'UEMOA
     * 
     * @param string $country
     * @return bool
     */
    public static function isUEMOA(string $country): bool
    {
        return in_array($country, self::UEMOA_COUNTRIES);
    }

    /**
     * Détermine l'origine d'un étudiant (uemoa, non_uemoa, exempted)
     * 
     * @param string $country
     * @param bool $isExempted
     * @return string
     */
    public static function getStudentOrigin(string $country, bool $isExempted = false): string
    {
        if ($isExempted) {
            return 'exempted';
        }

        return self::isUEMOA($country) ? 'uemoa' : 'non_uemoa';
    }
}

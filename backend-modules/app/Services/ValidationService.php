<?php

namespace App\Services;

class ValidationService
{
    /**
     * Valider un numéro IFU (13 chiffres)
     */
    public static function validateIFU(?string $ifu): bool
    {
        if (empty($ifu)) {
            return false;
        }

        $cleaned = trim($ifu);
        return preg_match('/^\d{13}$/', $cleaned) === 1;
    }

    /**
     * Valider un RIB (22 à 27 caractères alphanumériques)
     */
    public static function validateRIB(?string $rib): bool
    {
        if (empty($rib)) {
            return false;
        }

        $cleaned = strtoupper(trim($rib));
        $length = strlen($cleaned);
        
        return $length >= 22 && $length <= 27 && preg_match('/^[A-Z0-9]+$/', $cleaned) === 1;
    }

    /**
     * Obtenir le message d'erreur pour IFU
     */
    public static function getIFUErrorMessage(): string
    {
        return 'Le numéro IFU doit contenir exactement 13 chiffres.';
    }

    /**
     * Obtenir le message d'erreur pour RIB
     */
    public static function getRIBErrorMessage(): string
    {
        return 'Le RIB doit contenir entre 22 et 27 caractères alphanumériques.';
    }
}

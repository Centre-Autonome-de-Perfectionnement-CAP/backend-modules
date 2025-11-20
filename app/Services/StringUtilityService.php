<?php

namespace App\Services;

class StringUtilityService
{
    /**
     * Capitalise chaque mot d'une chaîne
     */
    public static function capitalize(string $text): string
    {
        return ucwords(strtolower(trim($text)));
    }
}
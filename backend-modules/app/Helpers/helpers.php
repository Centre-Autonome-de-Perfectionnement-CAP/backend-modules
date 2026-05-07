<?php

if (!function_exists('translateEnglishDateToFrench')) {
    function translateEnglishDateToFrench(string $date): string
    {
        $months = [
            'January' => 'Janvier',
            'February' => 'Février',
            'March' => 'Mars',
            'April' => 'Avril',
            'May' => 'Mai',
            'June' => 'Juin',
            'July' => 'Juillet',
            'August' => 'Août',
            'September' => 'Septembre',
            'October' => 'Octobre',
            'November' => 'Novembre',
            'December' => 'Décembre',
        ];

        return str_replace(array_keys($months), array_values($months), $date);
    }
}

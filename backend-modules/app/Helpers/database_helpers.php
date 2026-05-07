<?php

use App\Services\DatabaseAdapter;

if (!function_exists('db_month')) {
    /**
     * Extrait le mois d'une colonne de date
     */
    function db_month(string $column): string
    {
        return DatabaseAdapter::month($column);
    }
}

if (!function_exists('db_year')) {
    /**
     * Extrait l'année d'une colonne de date
     */
    function db_year(string $column): string
    {
        return DatabaseAdapter::year($column);
    }
}

if (!function_exists('db_day')) {
    /**
     * Extrait le jour d'une colonne de date
     */
    function db_day(string $column): string
    {
        return DatabaseAdapter::day($column);
    }
}

if (!function_exists('db_date_format')) {
    /**
     * Formate une date
     */
    function db_date_format(string $column, string $format): string
    {
        return DatabaseAdapter::dateFormat($column, $format);
    }
}

if (!function_exists('db_concat')) {
    /**
     * Concatène des colonnes
     */
    function db_concat(array $columns): string
    {
        return DatabaseAdapter::concat($columns);
    }
}

if (!function_exists('db_if_null')) {
    /**
     * Gère les valeurs NULL
     */
    function db_if_null(string $column, $default): string
    {
        return DatabaseAdapter::ifNull($column, $default);
    }
}

if (!function_exists('db_if')) {
    /**
     * Condition IF
     */
    function db_if(string $condition, $trueValue, $falseValue): string
    {
        return DatabaseAdapter::if($condition, $trueValue, $falseValue);
    }
}

if (!function_exists('db_group_concat')) {
    /**
     * GROUP_CONCAT / STRING_AGG
     */
    function db_group_concat(string $column, string $separator = ','): string
    {
        return DatabaseAdapter::groupConcat($column, $separator);
    }
}

if (!function_exists('db_cast')) {
    /**
     * Cast vers un type
     */
    function db_cast(string $column, string $type): string
    {
        return DatabaseAdapter::cast($column, $type);
    }
}

if (!function_exists('db_ilike')) {
    /**
     * Recherche insensible à la casse
     */
    function db_ilike(string $column, string $value): string
    {
        return DatabaseAdapter::ilike($column, $value);
    }
}

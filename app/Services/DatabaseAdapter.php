<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseAdapter
{
    /**
     * Détecte le driver de base de données actuel
     */
    private static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Vérifie si on utilise PostgreSQL
     */
    private static function isPostgres(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    /**
     * Vérifie si on utilise MySQL
     */
    private static function isMySQL(): bool
    {
        return in_array(self::getDriver(), ['mysql', 'mariadb']);
    }

    /**
     * Extrait le mois d'une date
     * MySQL: MONTH(date)
     * PostgreSQL: EXTRACT(MONTH FROM date)
     */
    public static function month(string $column): string
    {
        if (self::isPostgres()) {
            return "EXTRACT(MONTH FROM {$column})";
        }
        return "MONTH({$column})";
    }

    /**
     * Extrait l'année d'une date
     * MySQL: YEAR(date)
     * PostgreSQL: EXTRACT(YEAR FROM date)
     */
    public static function year(string $column): string
    {
        if (self::isPostgres()) {
            return "EXTRACT(YEAR FROM {$column})";
        }
        return "YEAR({$column})";
    }

    /**
     * Extrait le jour d'une date
     * MySQL: DAY(date)
     * PostgreSQL: EXTRACT(DAY FROM date)
     */
    public static function day(string $column): string
    {
        if (self::isPostgres()) {
            return "EXTRACT(DAY FROM {$column})";
        }
        return "DAY({$column})";
    }

    /**
     * Formate une date
     * MySQL: DATE_FORMAT(date, format)
     * PostgreSQL: TO_CHAR(date, format)
     */
    public static function dateFormat(string $column, string $format): string
    {
        if (self::isPostgres()) {
            // Conversion des formats MySQL vers PostgreSQL
            $pgFormat = self::convertDateFormatToPostgres($format);
            return "TO_CHAR({$column}, '{$pgFormat}')";
        }
        return "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * Concatène des chaînes
     * MySQL: CONCAT(str1, str2, ...)
     * PostgreSQL: CONCAT(str1, str2, ...) ou str1 || str2
     */
    public static function concat(array $columns): string
    {
        if (self::isPostgres()) {
            return implode(' || ', $columns);
        }
        return 'CONCAT(' . implode(', ', $columns) . ')';
    }

    /**
     * Gère les valeurs NULL
     * MySQL: IFNULL(column, default)
     * PostgreSQL: COALESCE(column, default)
     */
    public static function ifNull(string $column, $default): string
    {
        if (self::isPostgres()) {
            return "COALESCE({$column}, {$default})";
        }
        return "IFNULL({$column}, {$default})";
    }

    /**
     * Condition IF
     * MySQL: IF(condition, true_value, false_value)
     * PostgreSQL: CASE WHEN condition THEN true_value ELSE false_value END
     */
    public static function if(string $condition, $trueValue, $falseValue): string
    {
        if (self::isPostgres()) {
            return "CASE WHEN {$condition} THEN {$trueValue} ELSE {$falseValue} END";
        }
        return "IF({$condition}, {$trueValue}, {$falseValue})";
    }

    /**
     * GROUP_CONCAT
     * MySQL: GROUP_CONCAT(column SEPARATOR separator)
     * PostgreSQL: STRING_AGG(column, separator)
     */
    public static function groupConcat(string $column, string $separator = ','): string
    {
        if (self::isPostgres()) {
            return "STRING_AGG({$column}::text, '{$separator}')";
        }
        return "GROUP_CONCAT({$column} SEPARATOR '{$separator}')";
    }

    /**
     * Limite avec offset
     * MySQL: LIMIT offset, count
     * PostgreSQL: LIMIT count OFFSET offset
     */
    public static function limit(int $count, int $offset = 0): string
    {
        if (self::isPostgres()) {
            return $offset > 0 ? "LIMIT {$count} OFFSET {$offset}" : "LIMIT {$count}";
        }
        return $offset > 0 ? "LIMIT {$offset}, {$count}" : "LIMIT {$count}";
    }

    /**
     * Convertit les formats de date MySQL vers PostgreSQL
     */
    private static function convertDateFormatToPostgres(string $mysqlFormat): string
    {
        $conversions = [
            '%Y' => 'YYYY',  // Année sur 4 chiffres
            '%y' => 'YY',    // Année sur 2 chiffres
            '%m' => 'MM',    // Mois sur 2 chiffres
            '%d' => 'DD',    // Jour sur 2 chiffres
            '%H' => 'HH24',  // Heure (24h)
            '%h' => 'HH12',  // Heure (12h)
            '%i' => 'MI',    // Minutes
            '%s' => 'SS',    // Secondes
            '%M' => 'Month', // Nom du mois
            '%b' => 'Mon',   // Nom du mois abrégé
            '%W' => 'Day',   // Nom du jour
            '%a' => 'Dy',    // Nom du jour abrégé
        ];

        return str_replace(array_keys($conversions), array_values($conversions), $mysqlFormat);
    }

    /**
     * Cast vers un type spécifique
     * Utile pour les conversions de types
     */
    public static function cast(string $column, string $type): string
    {
        if (self::isPostgres()) {
            return "{$column}::{$type}";
        }
        return "CAST({$column} AS {$type})";
    }

    /**
     * Recherche insensible à la casse
     * MySQL: LIKE (case-insensitive par défaut)
     * PostgreSQL: ILIKE
     */
    public static function ilike(string $column, string $value): string
    {
        if (self::isPostgres()) {
            return "{$column} ILIKE {$value}";
        }
        return "{$column} LIKE {$value}";
    }

    /**
     * Expression régulière
     * MySQL: REGEXP
     * PostgreSQL: ~
     */
    public static function regexp(string $column, string $pattern): string
    {
        if (self::isPostgres()) {
            return "{$column} ~ '{$pattern}'";
        }
        return "{$column} REGEXP '{$pattern}'";
    }

    /**
     * Extraction JSON
     * MySQL: JSON_UNQUOTE(JSON_EXTRACT(column, '$.path'))
     * PostgreSQL: column->>'path'
     */
    public static function jsonExtract(string $column, string $path): string
    {
        if (self::isPostgres()) {
            // Convertir $.phone en 'phone'
            $cleanPath = str_replace('$.', '', $path);
            return "{$column}->'{$cleanPath}'";
        }
        return "JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$path}'))";
    }
}

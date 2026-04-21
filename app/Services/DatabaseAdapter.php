<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Database Adapter to handle differences between PostgreSQL and MySQL
 */
class DatabaseAdapter
{
    /**
     * Get the current database driver
     */
    public static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Check if the current database is PostgreSQL
     */
    public static function isPostgres(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    /**
     * Check if the current database is MySQL
     */
    public static function isMysql(): bool
    {
        return in_array(self::getDriver(), ['mysql', 'mariadb']);
    }

    /**
     * Get the appropriate CONCAT function for the current database
     */
    public static function concat(array $columns): string
    {
        if (self::isPostgres()) {
            return implode(' || ', $columns);
        }
        
        return 'CONCAT(' . implode(', ', $columns) . ')';
    }

    /**
     * Get the appropriate ILIKE/LIKE operator for case-insensitive search
     */
    public static function ilike(): string
    {
        return self::isPostgres() ? 'ILIKE' : 'LIKE';
    }

    /**
     * Cast a value to the appropriate type for the current database
     */
    public static function castToText(string $column): string
    {
        if (self::isPostgres()) {
            return "{$column}::text";
        }
        
        return "CAST({$column} AS CHAR)";
    }

    /**
     * Get the appropriate boolean value for the current database
     */
    public static function booleanValue(bool $value): int|bool
    {
        if (self::isMysql()) {
            return $value ? 1 : 0;
        }
        
        return $value;
    }

    /**
     * Get the appropriate JSON extract function
     */
    public static function jsonExtract(string $column, string $path): string
    {
        if (self::isPostgres()) {
            return "{$column}->'{$path}'";
        }
        
        return "JSON_EXTRACT({$column}, '$.{$path}')";
    }

    /**
     * Sanitize ID for database query
     * PostgreSQL is strict with types, so we need to ensure IDs are integers
     * 
     * @param mixed $id The ID to sanitize
     * @return int|null The sanitized ID or null if invalid
     */
    public static function sanitizeId($id): ?int
    {
        // Handle null, empty string, or 'null' string
        if ($id === null || $id === '' || $id === 'null') {
            return null;
        }

        // If it's numeric, cast to int
        if (is_numeric($id)) {
            return (int)$id;
        }

        // Otherwise, it's not a valid ID
        return null;
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

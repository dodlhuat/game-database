<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Changes the allowed values of an enum column on every driver we run on.
 * MySQL/MariaDB use a native ENUM, PostgreSQL a CHECK constraint named by
 * Laravel's convention. SQLite is left alone, its columns are relaxed elsewhere.
 */
class EnumColumn
{
    /** @param list<string> $values */
    public static function allow(string $table, string $column, array $values, ?string $default = null): void
    {
        $list = "'".implode("','", $values)."'";

        switch (DB::getDriverName()) {
            case 'mysql':
            case 'mariadb':
                $suffix = $default !== null ? " DEFAULT '{$default}'" : '';
                DB::statement("ALTER TABLE {$table} MODIFY COLUMN `{$column}` ENUM({$list}) NOT NULL{$suffix}");
                break;
            case 'pgsql':
                $constraint = "{$table}_{$column}_check";
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} CHECK (\"{$column}\" IN ({$list}))");
                if ($default !== null) {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN \"{$column}\" SET DEFAULT '{$default}'");
                }
                break;
        }
    }
}

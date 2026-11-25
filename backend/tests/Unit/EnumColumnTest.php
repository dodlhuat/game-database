<?php

namespace Tests\Unit;

use App\Support\EnumColumn;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The SQL EnumColumn sends per database. MySQL/MariaDB is exercised for real
 * by `make test-mariadb`; PostgreSQL (production) only here.
 */
class EnumColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // the test teardown asks the (mocked) manager for its connections
        DB::shouldReceive('getConnections')->andReturn([]);
    }

    public function test_postgres_replaces_the_check_constraint_and_sets_the_default(): void
    {
        DB::shouldReceive('getDriverName')->andReturn('pgsql');
        DB::shouldReceive('statement')->once()->ordered()
            ->with('ALTER TABLE loans DROP CONSTRAINT IF EXISTS loans_status_check');
        DB::shouldReceive('statement')->once()->ordered()
            ->with("ALTER TABLE loans ADD CONSTRAINT loans_status_check CHECK (\"status\" IN ('ACTIVE','RETURNED'))");
        DB::shouldReceive('statement')->once()->ordered()
            ->with("ALTER TABLE loans ALTER COLUMN \"status\" SET DEFAULT 'ACTIVE'");

        EnumColumn::allow('loans', 'status', ['ACTIVE', 'RETURNED'], 'ACTIVE');
    }

    public function test_mysql_modifies_the_enum_in_place(): void
    {
        DB::shouldReceive('getDriverName')->andReturn('mariadb');
        DB::shouldReceive('statement')->once()
            ->with("ALTER TABLE users MODIFY COLUMN `role` ENUM('USER','ADMIN') NOT NULL");

        EnumColumn::allow('users', 'role', ['USER', 'ADMIN']);
    }

    public function test_sqlite_is_left_alone(): void
    {
        DB::shouldReceive('getDriverName')->andReturn('sqlite');
        DB::shouldReceive('statement')->never();

        EnumColumn::allow('users', 'role', ['USER', 'ADMIN']);
    }
}

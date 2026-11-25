<?php

namespace Tests\Unit;

use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use PHPUnit\Framework\TestCase;

/**
 * The searches use whereLike(): case-insensitive on every database. Plain
 * 'like' is case-sensitive on PostgreSQL ("mia" would miss "Mia"), which the
 * SQLite test database cannot show — so check the SQL PostgreSQL would get.
 */
class CaseInsensitiveSearchTest extends TestCase
{
    public function test_postgres_gets_ilike(): void
    {
        $connection = new PostgresConnection(fn () => null);
        $query = (new Builder($connection, new PostgresGrammar($connection), new PostgresProcessor))
            ->from('users')
            ->whereLike('name', '%mia%')
            ->orWhereLike('email', '%mia%');

        $this->assertStringContainsString('ilike', $query->toSql());
        $this->assertStringNotContainsString(' like ', $query->toSql());
    }
}

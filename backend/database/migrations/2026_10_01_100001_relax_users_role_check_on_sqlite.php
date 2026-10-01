<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The MySQL-only migrations that added the SUPPORTER role skip SQLite, whose
 * enum CHECK constraint then rejects it (only matters for the test database).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', fn (Blueprint $table) => $table->string('role', 20)->default('USER')->change());
        }
    }

    public function down(): void
    {
        // Intentionally empty: the previous state was a constraint that rejected valid roles.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('USER', 'SUPPORTER', 'MEMBER', 'ADMIN') NOT NULL DEFAULT 'USER'");
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'USER' WHERE role = 'SUPPORTER'");

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('USER', 'MEMBER', 'ADMIN') NOT NULL DEFAULT 'USER'");
        }
    }
};

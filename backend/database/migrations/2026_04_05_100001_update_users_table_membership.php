<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the role ENUM to include USER and change the default
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('USER', 'MEMBER', 'ADMIN') NOT NULL DEFAULT 'USER'");

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('tokens')->default(0)->after('role');
            $table->timestamp('membership_expires_at')->nullable()->after('tokens');
        });

        // Existing MEMBER users (old default "registered" users) become USER
        DB::statement("UPDATE users SET role = 'USER' WHERE role = 'MEMBER'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'MEMBER' WHERE role = 'USER'");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tokens', 'membership_expires_at']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('MEMBER', 'ADMIN') NOT NULL DEFAULT 'MEMBER'");
    }
};

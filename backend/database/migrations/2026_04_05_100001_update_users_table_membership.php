<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop the existing CHECK constraint on role, widen to include USER, change default
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'USER'");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['USER'::text, 'MEMBER'::text, 'ADMIN'::text]))");

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('tokens')->default(0)->after('role');
            $table->timestamp('membership_expires_at')->nullable()->after('tokens');
        });

        // Existing MEMBER users (old default "registered" users) become USER
        DB::statement("UPDATE users SET role = 'USER' WHERE role = 'MEMBER'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tokens', 'membership_expires_at']);
        });

        DB::statement("UPDATE users SET role = 'MEMBER' WHERE role = 'USER'");
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'MEMBER'");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['MEMBER'::text, 'ADMIN'::text]))");
    }
};

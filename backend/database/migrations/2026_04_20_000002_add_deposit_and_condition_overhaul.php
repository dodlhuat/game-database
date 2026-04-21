<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            if (!Schema::hasColumn('games', 'deposit_tokens')) {
                $table->unsignedInteger('deposit_tokens')->default(0)->after('is_active');
            }
        });

        // MariaDB requires dropping the old enum and re-adding it
        Schema::table('copies', function (Blueprint $table) {
            if (!Schema::hasColumn('copies', 'borrow_count')) {
                $table->unsignedInteger('borrow_count')->default(0)->after('condition');
            }
        });

        DB::statement("ALTER TABLE copies MODIFY COLUMN `condition` ENUM('NEW','VERY_GOOD','GOOD','REVIEW','DAMAGED','LOCKED') NOT NULL DEFAULT 'NEW'");
        DB::statement("UPDATE copies SET `condition` = 'NEW'");

        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'deposit_tokens')) {
                $table->unsignedInteger('deposit_tokens')->default(0)->after('return_condition');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tokens_blocked')) {
                $table->unsignedInteger('tokens_blocked')->default(0)->after('tokens');
            }
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('deposit_tokens');
        });

        Schema::table('copies', function (Blueprint $table) {
            $table->dropColumn('borrow_count');
        });

        DB::statement("ALTER TABLE copies MODIFY COLUMN `condition` ENUM('GOOD','WORN','DAMAGED','LOCKED') NOT NULL DEFAULT 'GOOD'");

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('deposit_tokens');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tokens_blocked');
        });
    }
};

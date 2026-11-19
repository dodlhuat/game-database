<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Activity tracking for the inactive-account cleanup promised in the privacy
 * policy (accounts:prune-inactive). Existing accounts start their 3-year
 * clock now, since there is no earlier login history to go on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('remember_token');
            $table->timestamp('inactivity_notice_sent_at')->nullable()->after('last_seen_at');
            $table->timestamp('anonymized_at')->nullable()->after('inactivity_notice_sent_at');
        });

        DB::table('users')->update(['last_seen_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'inactivity_notice_sent_at', 'anonymized_at']);
        });
    }
};

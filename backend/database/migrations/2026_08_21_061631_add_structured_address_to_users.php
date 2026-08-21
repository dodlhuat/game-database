<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Structured replacement for the free-text `address` column.
            // `address` is kept as-is for existing members/supporters —
            // parsing their free-text entries into these columns would be
            // lossy and unverified, so it isn't backfilled.
            $table->string('street')->nullable()->after('address');
            $table->string('postal_code', 4)->nullable()->after('street');
            $table->string('city')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['street', 'postal_code', 'city']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable()->after('description');
            $table->unsignedSmallInteger('duration_max')->nullable()->after('duration_min');
            $table->unsignedTinyInteger('min_age')->nullable()->after('max_players');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'duration_max', 'min_age']);
        });
    }
};
